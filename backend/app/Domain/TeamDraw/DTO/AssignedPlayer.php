<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw\DTO;

use App\Domain\Enums\Position;

final readonly class AssignedPlayer
{
    public function __construct(
        public int $userId,
        public int $skill,
        public ?Position $assignedPosition,
        public bool $isGoalkeeper,
    ) {
    }
}
