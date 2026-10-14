<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw\DTO;

use App\Domain\Enums\Position;

final readonly class PlayerInput
{
    public function __construct(
        public int $userId,
        public int $skill,
        public ?Position $primaryPosition,
        public ?Position $secondaryPosition,
        public bool $isFixedGoalkeeper,
    ) {
    }

    public function effectiveLinePosition(): ?Position
    {
        if ($this->primaryPosition === Position::GOL) {
            return $this->secondaryPosition;
        }

        return $this->primaryPosition;
    }
}
