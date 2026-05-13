<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Service\AppointmentMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:appointments:remind',
    description: 'Envoie un mail de rappel aux patients qui ont un RDV dans la fenêtre [+23h, +25h].',
)]
final class SendRemindersCommand extends Command
{
    public function __construct(
        private readonly AppointmentMailer $mailer,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTimeImmutable();
        $from = $now->modify('+23 hours');
        $to = $now->modify('+25 hours');

        $candidates = $this->em->getRepository(Appointment::class)
            ->createQueryBuilder('a')
            ->innerJoin('a.slot', 's')
            ->andWhere('a.status = :status')
            ->andWhere('s.startAt BETWEEN :from AND :to')
            ->setParameter('status', AppointmentStatus::CONFIRMED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        $io->title('Envoi des rappels J-1');
        $io->writeln(sprintf('Fenêtre : <info>%s</info> → <info>%s</info>', $from->format('Y-m-d H:i'), $to->format('Y-m-d H:i')));
        $io->writeln(sprintf('Candidats trouvés : <info>%d</info>', count($candidates)));
        $io->newLine();

        $sent = 0;
        foreach ($candidates as $appointment) {
            $this->mailer->sendReminder($appointment);
            $sent++;
            $io->writeln(sprintf(
                '  · RDV #%d — %s avec %s',
                $appointment->getId(),
                $appointment->getSlot()->getStartAt()->format('Y-m-d H:i'),
                $appointment->getSlot()->getDoctor()->getUser()->getFullName(),
            ));
        }

        $io->newLine();
        $io->success(sprintf('%d rappel(s) envoyé(s).', $sent));

        return Command::SUCCESS;
    }
}
