<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

/**
 * Met à jour le profil "de base" d'un User (nom, prénom, téléphone, email).
 * L'email est synchronisé avec Firebase. Utilisé par l'admin (CRUD users)
 * et par l'utilisateur lui-même (page Mon Compte).
 */
final class UserProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FirebaseAuth $firebaseAuth,
    ) {}

    public function update(User $user, string $firstName, string $lastName, ?string $phone, string $email): void
    {
        if ('' === trim($firstName) || '' === trim($lastName) || '' === trim($email)) {
            throw new \DomainException('Prénom, nom et email sont obligatoires.');
        }

        $user->setFirstName($firstName)->setLastName($lastName)->setPhone($phone);

        if ($user->getEmail() !== $email) {
            $this->firebaseAuth->updateUser($user->getFirebaseUid(), [
                'email' => $email,
                'emailVerified' => true,
            ]);
            $user->setEmail($email);
        }

        $this->em->flush();
    }
}
