<?php

declare(strict_types=1);

namespace App\Domain\Ranking;

use App\Domain\Ranking\DTO\UserRankingStats;
use App\Domain\Ranking\DTO\VoteTally;

/**
 * Computa pontuação de ranking a partir de uma lista de votos.
 *
 * Regras (brief seção 3.6):
 *  - Voto recebido em categoria positiva: +points_per_vote (tipicamente +1).
 *  - Mais votado da sessão em categoria positiva: +bonus_for_winner (tipicamente +3).
 *  - Voto recebido em categoria humorística: points_per_vote já é negativo (-1); sem bônus.
 *  - Empates: todos os líderes recebem o bônus (regra amigável, não arbitrária).
 *  - Categorias sem nenhum voto: ninguém vence, ninguém pontua.
 */
final class RankingComputer
{
    /**
     * @param  list<VoteTally>  $votes
     * @return array<int, UserRankingStats>  mapa userId => stats agregado
     */
    public function compute(array $votes): array
    {
        /** @var array<int, array<int, int>> $votesPerCategoryPerUser */
        $votesPerCategoryPerUser = [];
        /** @var array<int, array{pointsPerVote:int, bonusForWinner:int, isPositive:bool}> $categoryMeta */
        $categoryMeta = [];

        foreach ($votes as $vote) {
            $categoryMeta[$vote->categoryId] ??= [
                'pointsPerVote' => $vote->pointsPerVote,
                'bonusForWinner' => $vote->bonusForWinner,
                'isPositive' => $vote->isPositive,
            ];

            $votesPerCategoryPerUser[$vote->categoryId][$vote->votedUserId] =
                ($votesPerCategoryPerUser[$vote->categoryId][$vote->votedUserId] ?? 0) + 1;
        }

        /** @var array<int, list<int>> $winnersByCategory */
        $winnersByCategory = [];
        foreach ($votesPerCategoryPerUser as $categoryId => $perUser) {
            if ($perUser === []) {
                continue;
            }
            if (! ($categoryMeta[$categoryId]['isPositive'] ?? false)) {
                continue;
            }

            $max = max($perUser);
            $winnersByCategory[$categoryId] = array_keys(array_filter(
                $perUser,
                static fn (int $count): bool => $count === $max,
            ));
        }

        /** @var array<int, array{points: array<int, int>, votes: array<int, int>, wins: array<int, bool>}> $byUser */
        $byUser = [];
        foreach ($votesPerCategoryPerUser as $categoryId => $perUser) {
            $meta = $categoryMeta[$categoryId];
            foreach ($perUser as $userId => $count) {
                $byUser[$userId]['points'][$categoryId] = ($byUser[$userId]['points'][$categoryId] ?? 0)
                    + ($count * $meta['pointsPerVote']);
                $byUser[$userId]['votes'][$categoryId] = ($byUser[$userId]['votes'][$categoryId] ?? 0) + $count;
                $byUser[$userId]['wins'][$categoryId] ??= false;
            }

            foreach ($winnersByCategory[$categoryId] ?? [] as $winnerId) {
                $byUser[$winnerId]['points'][$categoryId] = ($byUser[$winnerId]['points'][$categoryId] ?? 0)
                    + $meta['bonusForWinner'];
                $byUser[$winnerId]['wins'][$categoryId] = true;
            }
        }

        $result = [];
        foreach ($byUser as $userId => $data) {
            $total = array_sum($data['points']);
            $result[$userId] = new UserRankingStats(
                userId: $userId,
                totalPoints: $total,
                pointsByCategory: $data['points'],
                votesReceivedByCategory: $data['votes'] ?? [],
                winsByCategory: $data['wins'] ?? [],
            );
        }

        return $result;
    }
}
