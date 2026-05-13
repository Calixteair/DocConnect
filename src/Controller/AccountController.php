<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\Gender;
use App\Enum\UserRole;
use App\Repository\PatientRepository;
use App\Service\UserProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class AccountController extends AbstractController
{
    public function __construct(
        private readonly PatientRepository $patients,
        private readonly EntityManagerInterface $em,
        private readonly UserProfileService $profile,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/app/mon-compte', name: 'app_account', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->render('app/account.html.twig', [
            'user' => $user,
            'patient' => UserRole::PATIENT === $user->getRole()
                ? $this->patients->findByUserId((int) $user->getId())
                : null,
            'genders' => Gender::cases(),
        ]);
    }

    #[Route('/app/mon-compte', name: 'app_account_update', methods: ['POST'])]
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('account_update', (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'Session expirée, réessayez.');
            return $this->redirectToRoute('app_account');
        }

        try {
            $this->profile->update(
                $user,
                trim((string) $request->request->get('firstName', '')),
                trim((string) $request->request->get('lastName', '')),
                trim((string) $request->request->get('phone', '')) ?: null,
                trim((string) $request->request->get('email', '')),
            );

            if (UserRole::PATIENT === $user->getRole()) {
                $this->applyPatientFields($user, $request);
                $this->em->flush();
            }

            $this->addFlash('success', 'Profil mis à jour.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Account update failed', ['user_id' => $user->getId(), 'reason' => $e->getMessage()]);
            $this->addFlash('error', 'Impossible de mettre à jour le profil. Réessayez ou contactez le support.');
        }

        return $this->redirectToRoute('app_account');
    }

    private function applyPatientFields(User $user, Request $request): void
    {
        $patient = $this->patients->findByUserId((int) $user->getId());
        if (null === $patient) {
            return;
        }

        $birthdateRaw = trim((string) $request->request->get('birthdate', ''));
        if ('' === $birthdateRaw) {
            throw new \DomainException('Date de naissance obligatoire.');
        }
        $birthdate = \DateTimeImmutable::createFromFormat('Y-m-d', $birthdateRaw);
        if (false === $birthdate || $birthdate->format('Y-m-d') !== $birthdateRaw) {
            throw new \DomainException('Date de naissance invalide (format attendu AAAA-MM-JJ).');
        }
        $patient->setBirthdate($birthdate);

        $genderRaw = (string) $request->request->get('gender', '');
        $gender = Gender::tryFrom($genderRaw);
        if (null !== $gender) {
            $patient->setGender($gender);
        }

        $patient->setAllergies(trim((string) $request->request->get('allergies', '')) ?: null);
    }
}
