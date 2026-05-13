<?php

declare(strict_types=1);

namespace App\Enum;

enum SlotStatus: string
{
    case OPEN = 'OPEN';
    case BOOKED = 'BOOKED';
    case BLOCKED = 'BLOCKED';
}
