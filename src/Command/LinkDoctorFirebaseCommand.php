<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Doctor;
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
    name: 'app:demo:link-doctor-firebase',
    description: 'Crée/met-à-jour un compte Firebase et le lie au User MariaDB d\'un Doctor existant.',
)]
final class LinkDoctorFirebaseCommand extends Command
{
    public function __construct(
        private readonly FirebaseAuth $firebaseAuth,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email Firebase à créer ou réutiliser.')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe Firebase (min 6 caractères).')
            ->addArgument('doctorSlug', InputArgument::REQUIRED, 'Slug du Doctor à lier (ex: dr-aymen-ben-ali).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');
        $slug = (string) $input->getArgument('doctorSlug');

        $doctor = $this->em->getRepository(Doctor::class)->findOneBy(['slug' => $slug]);
        if (null === $doctor) {
            $io->error(sprintf('Aucun Doctor trouvé avec le slug "%s".', $slug));
            return Command::FAILURE;
        }

        $user = $doctor->getUser();

        try {
            $fbUser = $this->firebaseAuth->getUserByEmail($email);
            $this->firebaseAuth->updateUser($fbUser->uid, ['password' => $password, 'emailVerified' => true]);
            $io->note(sprintf('Compte Firebase existant réutilisé (uid=%s).', $fbUser->uid));
        } catch (UserNotFound) {
            $fbUser = $this->firebaseAuth->createUser([
                'email' => $email,
                'emailVerified' => true,
                'password' => $password,
                'displayName' => $user->getFullName(),
            ]);
            $io->note(sprintf('Nouveau compte Firebase créé (uid=%s).', $fbUser->uid));
        }

        $user->relinkFirebaseUid($fbUser->uid)->setEmail($email);
        $this->em->flush();

        $io->success(sprintf(
            'Doctor "%s" (%s) lié au compte Firebase %s.',
            $doctor->getSlug(),
            $user->getFullName(),
            $email,
        ));

        return Command::SUCCESS;
    }
}
