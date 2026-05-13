<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

/**
 * Envoie les mails transactionnels liés à un Appointment via l'API Mailtrap.
 *
 * On utilise l'API HTTP (et non SMTP) avec le SDK officiel railsware/mailtrap-php.
 * Échec mail = log + on continue. Une réservation ne doit jamais être bloquée
 * par une coupure SMTP.
 */
final class AppointmentMailer
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly UrlGeneratorInterface $urls,
        private readonly LoggerInterface $logger,
        private readonly string $apiToken,
        private readonly int $inboxId,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {}

    public function sendConfirmation(Appointment $appointment): void
    {
        $patient = $appointment->getPatient()->getUser();
        $doctor = $appointment->getSlot()->getDoctor()->getUser();

        $this->dispatch($appointment, $patient, 'email/appointment_confirmed', 'Rendez-vous confirmé');
        $this->dispatch($appointment, $doctor, 'email/appointment_confirmed', 'Nouveau rendez-vous');
    }

    public function sendCancellation(Appointment $appointment): void
    {
        $patient = $appointment->getPatient()->getUser();
        $doctor = $appointment->getSlot()->getDoctor()->getUser();

        $this->dispatch($appointment, $patient, 'email/appointment_cancelled', 'Rendez-vous annulé');
        $this->dispatch($appointment, $doctor, 'email/appointment_cancelled', 'Rendez-vous annulé');
    }

    public function sendReminder(Appointment $appointment): void
    {
        $patient = $appointment->getPatient()->getUser();
        $this->dispatch($appointment, $patient, 'email/appointment_reminder', 'Rappel : rendez-vous demain');
    }

    private function dispatch(Appointment $appointment, User $recipient, string $template, string $subject): void
    {
        if ('' === $this->apiToken) {
            $this->logger->warning('Mailtrap API token manquant — mail non envoyé.', [
                'template' => $template,
                'to' => $recipient->getEmail(),
            ]);
            return;
        }

        $context = [
            'appointment' => $appointment,
            'slot' => $appointment->getSlot(),
            'doctor' => $appointment->getSlot()->getDoctor(),
            'patient' => $appointment->getPatient(),
            'recipient' => $recipient,
            'appointmentUrl' => $this->urls->generate(
                'app_my_appointments',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];

        try {
            $html = $this->twig->render($template . '.html.twig', $context);
            $text = $this->twig->render($template . '.txt.twig', $context);

            $email = (new MailtrapEmail())
                ->from(new Address($this->fromAddress, $this->fromName))
                ->to(new Address($recipient->getEmail(), $recipient->getFullName()))
                ->subject($subject . ' — ' . $appointment->getSlot()->getDoctor()->getUser()->getFullName())
                ->category('DocConnect - ' . $template)
                ->html($html)
                ->text($text);

            // Sandbox Email Testing : tous les mails sont interceptés dans l'inbox
            // Mailtrap. Aucun envoi réel — sûr pour démo/dev, marche pour n'importe
            // quelle adresse destinataire sans nécessiter un domaine vérifié.
            MailtrapClient::initSendingEmails(
                apiKey: $this->apiToken,
                isSandbox: true,
                inboxId: $this->inboxId,
            )->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Mail send failed', [
                'template' => $template,
                'to' => $recipient->getEmail(),
                'appointment_id' => $appointment->getId(),
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
