<?php

declare(strict_types=1);

namespace App\Service;

use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;

/**
 * Idempotent "ensure a Firebase account exists" : si l'email est connu, met à jour
 * le password ; sinon crée le compte. Renvoie toujours un UserRecord exploitable.
 */
final class FirebaseUserSync
{
    public function __construct(private readonly FirebaseAuth $firebaseAuth) {}

    public function ensureUser(string $email, string $password, string $displayName): UserRecord
    {
        try {
            $existing = $this->firebaseAuth->getUserByEmail($email);
            $this->firebaseAuth->updateUser($existing->uid, [
                'password' => $password,
                'emailVerified' => true,
            ]);
            return $this->firebaseAuth->getUser($existing->uid);
        } catch (UserNotFound) {
            return $this->firebaseAuth->createUser([
                'email' => $email,
                'password' => $password,
                'emailVerified' => true,
                'displayName' => $displayName,
            ]);
        }
    }
}
