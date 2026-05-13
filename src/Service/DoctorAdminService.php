<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\DoctorFormData;
use App\Entity\Address;
use App\Entity\Doctor;
use App\Entity\Specialty;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\DoctorRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Auth\UserRecord;
use Psr\Log\LoggerInterface;

final class DoctorAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FirebaseAuth $firebaseAuth,
        private readonly FirebaseUserSync $firebaseSync,
        private readonly DoctorRepository $doctors,
        private readonly UserRepository $users,
        private readonly SpecialtyRepository $specialties,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(DoctorFormData $data, string $password): Doctor
    {
        $this->assertSlugAvailable($data->slug);
        $this->assertEmailNotHijacked($data->email);

        $fbUser = $this->firebaseSync->ensureUser(
            $data->email,
            $password,
            trim($data->firstName . ' ' . $data->lastName),
        );

        $user = $this->upsertUser($fbUser, $data);

        $doctor = new Doctor($user, $data->slug, $data->price);
        $doctor->setRpps($data->rpps)->setBio($data->bio)
            ->setAcceptVisio($data->acceptVisio)
            ->setLanguages($data->languages);

        foreach ($this->loadSpecialties($data->specialtyIds) as $s) {
            $doctor->addSpecialty($s);
        }

        $doctor->addAddress(new Address($data->street, $data->city, $data->postalCode));

        $this->em->persist($doctor);
        $this->em->flush();

        return $doctor;
    }

    private function upsertUser(UserRecord $fbUser, DoctorFormData $data): User
    {
        $user = $this->users->findByFirebaseUid($fbUser->uid)
            ?? $this->users->findByEmail($data->email)
            ?? new User($fbUser->uid, $data->email, $data->firstName, $data->lastName);

        $user->relinkFirebaseUid($fbUser->uid)
            ->setEmail($data->email)
            ->setFirstName($data->firstName)
            ->setLastName($data->lastName)
            ->setPhone($data->phone)
            ->setRole(UserRole::DOCTOR);

        if (null === $user->getId()) {
            $this->em->persist($user);
        }

        return $user;
    }

    /**
     * Bloque la "prise de contrôle" d'un compte PATIENT/ADMIN existant en réutilisant
     * son email à la création d'un médecin. Un médecin pré-existant est OK (relink).
     */
    private function assertEmailNotHijacked(string $email): void
    {
        $existing = $this->users->findByEmail($email);
        if (null !== $existing && UserRole::DOCTOR !== $existing->getRole()) {
            throw new \DomainException(sprintf(
                'L\'email "%s" appartient à un compte %s. Choisissez un autre email.',
                $email,
                strtolower($existing->getRole()->value),
            ));
        }
    }

    public function update(Doctor $doctor, DoctorFormData $data): void
    {
        if ($doctor->getSlug() !== $data->slug) {
            $this->assertSlugAvailable($data->slug);
            $doctor->setSlug($data->slug);
        }

        $user = $doctor->getUser();
        if ($user->getEmail() !== $data->email) {
            $this->firebaseAuth->updateUser($user->getFirebaseUid(), ['email' => $data->email, 'emailVerified' => true]);
            $user->setEmail($data->email);
        }
        $user->setFirstName($data->firstName)
            ->setLastName($data->lastName)
            ->setPhone($data->phone);

        $doctor->setRpps($data->rpps)->setBio($data->bio)
            ->setPrice($data->price)
            ->setAcceptVisio($data->acceptVisio)
            ->setLanguages($data->languages);

        $this->syncSpecialties($doctor, $data->specialtyIds);

        $first = $doctor->getAddresses()->first();
        if ($first instanceof Address) {
            $first->setStreet($data->street)->setCity($data->city)->setPostalCode($data->postalCode);
        } else {
            $doctor->addAddress(new Address($data->street, $data->city, $data->postalCode));
        }

        $this->em->flush();
    }

    public function delete(Doctor $doctor): void
    {
        $user = $doctor->getUser();
        $uid = $user->getFirebaseUid();
        $doctorId = $doctor->getId();

        $this->em->remove($user);
        $this->em->flush();

        try {
            $this->firebaseAuth->deleteUser($uid);
        } catch (UserNotFound) {
            // Déjà absent, rien à faire.
        } catch (\Throwable $e) {
            $this->logger->error('Échec suppression Firebase après delete Doctor', [
                'doctor_id' => $doctorId,
                'firebase_uid' => $uid,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private function assertSlugAvailable(string $slug): void
    {
        if (null !== $this->doctors->findOneBy(['slug' => $slug])) {
            throw new \DomainException(sprintf('Le slug "%s" est déjà utilisé.', $slug));
        }
    }

    /**
     * @param list<int> $ids
     * @return list<Specialty>
     */
    private function loadSpecialties(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }
        return $this->specialties->findBy(['id' => $ids]);
    }

    /**
     * @param list<int> $ids
     */
    private function syncSpecialties(Doctor $doctor, array $ids): void
    {
        $wanted = [];
        foreach ($this->loadSpecialties($ids) as $s) {
            $wanted[$s->getId()] = $s;
        }

        foreach ($doctor->getSpecialties() as $current) {
            if (!isset($wanted[$current->getId()])) {
                $doctor->removeSpecialty($current);
            }
        }
        foreach ($wanted as $s) {
            $doctor->addSpecialty($s);
        }
    }
}
