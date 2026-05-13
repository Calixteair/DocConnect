<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo:create-admin',
    description: 'Crée (ou met à jour) un compte ADMIN dans Firebase + MariaDB.',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly FirebaseAuth $firebaseAuth,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email admin', 'admin@docconnect.tn')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe (≥ 6 caractères)', 'admin@docconnect');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');

        try {
            $fbUser = $this->firebaseAuth->getUserByEmail($email);
            $this->firebaseAuth->updateUser($fbUser->uid, ['password' => $password, 'emailVerified' => true]);
            $io->note(sprintf('Compte Firebase existant réutilisé (uid=%s).', $fbUser->uid));
        } catch (UserNotFound) {
            $fbUser = $this->firebaseAuth->createUser([
                'email' => $email,
                'emailVerified' => true,
                'password' => $password,
                'displayName' => 'Admin DocConnect',
            ]);
            $io->note(sprintf('Compte Firebase créé (uid=%s).', $fbUser->uid));
        }

        $user = $this->users->findByFirebaseUid($fbUser->uid) ?? $this->users->findByEmail($email);

        if (null === $user) {
            $user = new User($fbUser->uid, $email, 'Admin', 'DocConnect');
            $this->em->persist($user);
        } else {
            $user->relinkFirebaseUid($fbUser->uid)->setEmail($email);
        }
        $user->setRole(UserRole::ADMIN);
        $this->em->flush();

        $io->success(sprintf('Admin %s prêt à se connecter avec le mot de passe fourni.', $email));
        return Command::SUCCESS;
    }
}
