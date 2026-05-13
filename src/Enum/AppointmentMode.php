<?php

declare(strict_types=1);

namespace App\Enum;

enum AppointmentMode: string
{
    case PHYSICAL = 'PHYSICAL';
    case VIDEO = 'VIDEO';
}
