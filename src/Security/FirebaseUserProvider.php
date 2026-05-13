<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
final class FirebaseUserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepository $users) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findByFirebaseUid($identifier);

        if (null === $user) {
            throw new UserNotFoundException(sprintf('Aucun compte DocConnect pour l\'UID Firebase %s.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \LogicException('FirebaseUserProvider ne gère que App\Entity\User.');
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
