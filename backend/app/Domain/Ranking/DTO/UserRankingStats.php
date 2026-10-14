<?php

declare(strict_types=1);

namespace App\Domain\Ranking\DTO;

final readonly class UserRankingStats
{
    /**
     * @param  array<int, int>  $pointsByCategory      categoryId => pontos somados nesta compute
     * @param  array<int, int>  $votesReceivedByCategory   categoryId => nº de votos recebidos
     * @param  array<int, bool> $winsByCategory        categoryId => foi o mais votado (positiva)
     */
    public function __construct(
        public int $userId,
        public int $totalPoints,
        public array $pointsByCategory,
        public array $votesReceivedByCategory,
        public array $winsByCategory,
    ) {
    }
}
