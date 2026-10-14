<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum PresenceStatus: string
{
    case CONFIRMED = 'confirmed';
    case DECLINED = 'declined';
}
