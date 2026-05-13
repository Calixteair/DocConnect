<?php

declare(strict_types=1);

namespace App\Enum;

enum AppointmentStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case DONE = 'DONE';
}
