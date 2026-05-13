<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Patient;
use App\Entity\Slot;
use App\Enum\AppointmentMode;
use App\Enum\SlotStatus;
use App\Exception\SlotAlreadyBookedException;
use App\Service\AppointmentMailer;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cœur fonctionnel de la réservation.
 *
 * Garantit l'atomicité : 2 patients qui réservent le même créneau ne peuvent pas
 * tous les deux réussir grâce à un SELECT … FOR UPDATE (LockMode::PESSIMISTIC_WRITE)
 * dans une transaction Doctrine.
 */
final class AppointmentBookingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly AppointmentMailer $mailer,
    ) {}

    public function book(int $slotId, Patient $patient, ?string $motif = null): Appointment
    {
        $this->em->beginTransaction();
        try {
            /** @var Slot|null $slot */
            $slot = $this->em->find(Slot::class, $slotId, LockMode::PESSIMISTIC_WRITE);

            if (null === $slot || SlotStatus::OPEN !== $slot->getStatus()) {
                $this->em->rollback();
                throw new SlotAlreadyBookedException($slotId);
            }

            $appointment = new Appointment($slot, $patient);
            if (null !== $motif && '' !== trim($motif)) {
                $appointment->setMotif(trim($motif));
            }
            $appointment->confirm();

            if (AppointmentMode::VIDEO === $slot->getMode()) {
                $appointment->setVideoRoom($this->generateVideoRoom());
            }

            $slot->markBooked();

            $this->em->persist($appointment);
            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Booking créé', [
                'appointment_id' => $appointment->getId(),
                'slot_id' => $slotId,
                'patient_id' => $patient->getId(),
            ]);

            // Mail post-commit : si l'envoi échoue, on log mais on ne reverse pas la résa.
            $this->mailer->sendConfirmation($appointment);

            return $appointment;
        } catch (SlotAlreadyBookedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('Booking échec', [
                'slot_id' => $slotId,
                'patient_id' => $patient->getId(),
                'reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function generateVideoRoom(): string
    {
        return sprintf('docconnect-%s', bin2hex(random_bytes(8)));
    }
}
