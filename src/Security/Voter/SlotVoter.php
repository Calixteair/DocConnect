<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Slot;
use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Slot>
 */
final class SlotVoter extends Voter
{
    public const EDIT = 'SLOT_EDIT';
    public const DELETE = 'SLOT_DELETE';

    private const SUPPORTED = [self::EDIT, self::DELETE];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED, true) && $subject instanceof Slot;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (UserRole::ADMIN === $user->getRole()) {
            return true;
        }

        /** @var Slot $subject */
        return $subject->getDoctor()->getUser()->getId() === $user->getId();
    }
}
