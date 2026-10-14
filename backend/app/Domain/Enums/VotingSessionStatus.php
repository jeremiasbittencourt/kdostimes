<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum VotingSessionStatus: string
{
    case PENDING = 'pending';
    case OPEN = 'open';
    case CLOSED = 'closed';
}
