<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum MemberRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
}
