<?php

declare(strict_types=1);

namespace App\Domain\Ranking\DTO;

final readonly class VoteTally
{
    public function __construct(
        public int $categoryId,
        public int $pointsPerVote,
        public int $bonusForWinner,
        public bool $isPositive,
        public int $votedUserId,
    ) {
    }
}
