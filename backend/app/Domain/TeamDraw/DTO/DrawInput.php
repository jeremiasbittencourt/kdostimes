<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw\DTO;

final readonly class DrawInput
{
    /**
     * @param  list<PlayerInput>  $players
     */
    public function __construct(
        public array $players,
        public int $playersPerTeam,
        public bool $hasFixedGoalkeepers,
        public int $starTolerance,
        public float $positionMaxRatio,
    ) {
    }
}
