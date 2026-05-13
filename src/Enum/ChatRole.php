<?php

declare(strict_types=1);

namespace App\Enum;

enum ChatRole: string
{
    case USER = 'USER';
    case ASSISTANT = 'ASSISTANT';
    case SYSTEM = 'SYSTEM';
}
