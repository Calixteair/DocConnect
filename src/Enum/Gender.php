<?php

declare(strict_types=1);

namespace App\Enum;

enum Gender: string
{
    case FEMALE = 'FEMALE';
    case MALE = 'MALE';
    case OTHER = 'OTHER';
}
