<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Patient;
use App\Entity\User;
use App\Enum\Gender;
use App\Enum\UserRole;
use App\Repository\PatientRepository;
use App\Repository\UserRepository;
use App\Service\FirebaseUserSync;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo:create-patient',
    description: 'Crée (ou met à jour) un compte PATIENT dans Firebase + MariaDB.',
)]
final class CreatePatientCommand extends Command
{
    public function __construct(
        private readonly FirebaseUserSync $firebaseSync,
        private readonly UserRepository $users,
        private readonly PatientRepository $patients,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email patient', 'demo@docconnect.tn')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe (≥ 6)', 'demo1234')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'Prénom', 'Sami')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Nom', 'Démo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');
        $firstName = (string) $input->getArgument('firstName');
        $lastName = (string) $input->getArgument('lastName');

        $fbUser = $this->firebaseSync->ensureUser($email, $password, trim($firstName . ' ' . $lastName));
        $io->note(sprintf('Compte Firebase prêt (uid=%s).', $fbUser->uid));

        $user = $this->users->findByFirebaseUid($fbUser->uid)
            ?? $this->users->findByEmail($email)
            ?? new User($fbUser->uid, $email, $firstName, $lastName);

        $user->relinkFirebaseUid($fbUser->uid)
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRole(UserRole::PATIENT);

        if (null === $user->getId()) {
            $this->em->persist($user);
            $this->em->flush();
        }

        if (null === $this->patients->findByUserId((int) $user->getId())) {
            $this->em->persist(new Patient($user, new \DateTimeImmutable('1995-06-15'), Gender::MALE));
        }

        $this->em->flush();

        $io->success(sprintf('Patient %s prêt à se connecter.', $email));
        return Command::SUCCESS;
    }
}
