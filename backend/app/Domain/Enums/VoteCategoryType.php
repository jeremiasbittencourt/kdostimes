<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum VoteCategoryType: string
{
    case POSITIVE = 'positive';
    case HUMOR = 'humor';
}
