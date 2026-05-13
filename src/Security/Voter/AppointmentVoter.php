<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Appointment;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Appointment>
 */
final class AppointmentVoter extends Voter
{
    public const VIEW = 'APPOINTMENT_VIEW';
    public const CANCEL = 'APPOINTMENT_CANCEL';
    public const CONFIRM = 'APPOINTMENT_CONFIRM';

    private const SUPPORTED = [self::VIEW, self::CANCEL, self::CONFIRM];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED, true) && $subject instanceof Appointment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Appointment $subject */
        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::CANCEL => $this->canCancel($subject, $user),
            self::CONFIRM => $this->canConfirm($subject, $user),
        };
    }

    private function canView(Appointment $appointment, User $user): bool
    {
        return $this->isOwnerPatient($appointment, $user)
            || $this->isOwnerDoctor($appointment, $user)
            || UserRole::ADMIN === $user->getRole();
    }

    private function canCancel(Appointment $appointment, User $user): bool
    {
        if (AppointmentStatus::DONE === $appointment->getStatus()
            || AppointmentStatus::CANCELLED === $appointment->getStatus()) {
            return false;
        }

        return $this->isOwnerPatient($appointment, $user)
            || $this->isOwnerDoctor($appointment, $user);
    }

    private function canConfirm(Appointment $appointment, User $user): bool
    {
        if (AppointmentStatus::PENDING !== $appointment->getStatus()) {
            return false;
        }

        return $this->isOwnerDoctor($appointment, $user);
    }

    private function isOwnerPatient(Appointment $appointment, User $user): bool
    {
        $patient = $appointment->getPatient();
        return $patient->getUser()->getId() === $user->getId();
    }

    private function isOwnerDoctor(Appointment $appointment, User $user): bool
    {
        $doctor = $appointment->getSlot()->getDoctor();
        return $doctor->getUser()->getId() === $user->getId();
    }
}
