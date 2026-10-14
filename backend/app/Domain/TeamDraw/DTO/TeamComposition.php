<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw\DTO;

final readonly class TeamComposition
{
    /**
     * @param  list<AssignedPlayer>  $players
     */
    public function __construct(
        public string $name,
        public array $players,
    ) {
    }

    public function totalRating(): int
    {
        $sum = 0;
        foreach ($this->players as $player) {
            if ($player->isGoalkeeper) {
                continue;
            }
            $sum += $player->skill;
        }

        return $sum;
    }

    public function size(): int
    {
        return count($this->players);
    }

    public function lineSize(): int
    {
        $size = 0;
        foreach ($this->players as $player) {
            if (! $player->isGoalkeeper) {
                $size++;
            }
        }

        return $size;
    }
}
