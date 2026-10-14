<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum SessionStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case FINISHED = 'finished';
}
