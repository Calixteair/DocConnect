<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case PATIENT = 'PATIENT';
    case DOCTOR = 'DOCTOR';
    case ADMIN = 'ADMIN';

    public function symfonyRole(): string
    {
        return 'ROLE_' . $this->value;
    }
}
