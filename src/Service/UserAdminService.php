<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Psr\Log\LoggerInterface;

final class UserAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FirebaseAuth $firebaseAuth,
        private readonly UserRepository $users,
        private readonly UserProfileService $profile,
        private readonly LoggerInterface $logger,
    ) {}

    public function updateProfile(User $user, string $firstName, string $lastName, ?string $phone, string $email): void
    {
        $this->profile->update($user, $firstName, $lastName, $phone, $email);
    }

    public function changeRole(User $user, UserRole $newRole): void
    {
        if (UserRole::ADMIN === $user->getRole() && UserRole::ADMIN !== $newRole) {
            $this->assertNotLastAdmin();
        }
        $user->setRole($newRole);
        $this->em->flush();
    }

    public function delete(User $user): void
    {
        if (UserRole::ADMIN === $user->getRole()) {
            $this->assertNotLastAdmin();
        }

        $uid = $user->getFirebaseUid();
        $userId = $user->getId();

        $this->em->remove($user);
        $this->em->flush();

        try {
            $this->firebaseAuth->deleteUser($uid);
        } catch (UserNotFound) {
            // Compte déjà absent côté Firebase, rien à faire.
        } catch (\Throwable $e) {
            // L'user MariaDB est supprimé : on log mais on n'échoue pas — l'admin
            // peut nettoyer manuellement le compte Firebase si besoin.
            $this->logger->error('Échec suppression Firebase après delete MariaDB', [
                'user_id' => $userId,
                'firebase_uid' => $uid,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    private function assertNotLastAdmin(): void
    {
        if ($this->users->countByRole(UserRole::ADMIN) <= 1) {
            throw new \DomainException('Impossible : il doit rester au moins un administrateur.');
        }
    }
}
