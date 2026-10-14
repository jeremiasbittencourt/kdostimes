<?php

declare(strict_types=1);

namespace App\Services\Ranking;

use App\Domain\Ranking\DTO\VoteTally;
use App\Domain\Ranking\RankingComputer;
use App\Models\Pelada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Produz o ranking de uma pelada de acordo com o filtro de período.
 *
 * - "all": soma direta de ranking_entries (materializado, rápido).
 * - "month" | "year" | "last10": agrega dinamicamente de votes + voting_categories,
 *   reusando o RankingComputer. Evita materializar por período (overkill para v1).
 */
class RankingQuery
{
    public function __construct(
        private readonly RankingComputer $computer,
    ) {
    }

    /**
     * @return list<array{user_id:int, total_points:int, wins_count:int, per_category: array<int, array{points:int, wins:int}>}>
     */
    public function forPelada(Pelada $pelada, string $period): array
    {
        return match ($period) {
            'all' => $this->fromMaterialized($pelada),
            'month' => $this->fromVotesInRange($pelada, CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth()),
            'year' => $this->fromVotesInRange($pelada, CarbonImmutable::now()->startOfYear(), CarbonImmutable::now()->endOfYear()),
            'last10' => $this->fromLastSessions($pelada, 10),
            default => $this->fromMaterialized($pelada),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fromMaterialized(Pelada $pelada): array
    {
        $rows = DB::table('ranking_entries')
            ->where('pelada_id', $pelada->id)
            ->get();

        return $this->aggregateRankingRows($rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fromVotesInRange(Pelada $pelada, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $votes = $this->fetchVotes($pelada)
            ->whereBetween('votes.created_at', [$start, $end])
            ->get();

        return $this->aggregateFromVotes($votes);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fromLastSessions(Pelada $pelada, int $n): array
    {
        $sessionIds = DB::table('pelada_sessions')
            ->where('pelada_id', $pelada->id)
            ->orderByDesc('scheduled_at')
            ->limit($n)
            ->pluck('id')
            ->all();

        if ($sessionIds === []) {
            return [];
        }

        $votes = $this->fetchVotes($pelada)
            ->whereIn('voting_sessions.pelada_session_id', $sessionIds)
            ->get();

        return $this->aggregateFromVotes($votes);
    }

    private function fetchVotes(Pelada $pelada): \Illuminate\Database\Query\Builder
    {
        return DB::table('votes')
            ->join('voting_sessions', 'voting_sessions.id', '=', 'votes.voting_session_id')
            ->join('pelada_sessions', 'pelada_sessions.id', '=', 'voting_sessions.pelada_session_id')
            ->join('voting_categories', 'voting_categories.id', '=', 'votes.voting_category_id')
            ->where('pelada_sessions.pelada_id', $pelada->id)
            ->select([
                'votes.voting_category_id as categoryId',
                'votes.voted_user_id as votedUserId',
                'voting_categories.points_per_vote as pointsPerVote',
                'voting_categories.bonus_for_winner as bonusForWinner',
                'voting_categories.type as type',
                'voting_sessions.id as votingSessionId',
                'votes.created_at as createdAt',
            ]);
    }

    /**
     * @param  Collection<int, object>  $votes
     * @return list<array<string, mixed>>
     */
    private function aggregateFromVotes(Collection $votes): array
    {
        $bySession = $votes->groupBy('votingSessionId');

        /** @var array<int, array{total:int, wins:int, per_category: array<int, array{points:int, wins:int}>}> $accumulator */
        $accumulator = [];

        foreach ($bySession as $sessionVotes) {
            $tallies = $sessionVotes->map(fn ($row): VoteTally => new VoteTally(
                categoryId: (int) $row->categoryId,
                pointsPerVote: (int) $row->pointsPerVote,
                bonusForWinner: (int) $row->bonusForWinner,
                isPositive: $row->type === 'positive',
                votedUserId: (int) $row->votedUserId,
            ))->all();

            $stats = $this->computer->compute($tallies);

            foreach ($stats as $userId => $userStats) {
                $accumulator[$userId]['total'] = ($accumulator[$userId]['total'] ?? 0) + $userStats->totalPoints;

                foreach ($userStats->pointsByCategory as $categoryId => $points) {
                    $accumulator[$userId]['per_category'][$categoryId]['points'] =
                        ($accumulator[$userId]['per_category'][$categoryId]['points'] ?? 0) + $points;

                    if (($userStats->winsByCategory[$categoryId] ?? false) === true) {
                        $accumulator[$userId]['wins'] = ($accumulator[$userId]['wins'] ?? 0) + 1;
                        $accumulator[$userId]['per_category'][$categoryId]['wins'] =
                            ($accumulator[$userId]['per_category'][$categoryId]['wins'] ?? 0) + 1;
                    }
                }
            }
        }

        return $this->sortAndFormat($accumulator);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function aggregateRankingRows(Collection $rows): array
    {
        $accumulator = [];
        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $categoryId = (int) $row->voting_category_id;
            $points = (int) $row->total_points;
            $wins = (int) $row->wins_count;

            $accumulator[$userId]['total'] = ($accumulator[$userId]['total'] ?? 0) + $points;
            $accumulator[$userId]['wins'] = ($accumulator[$userId]['wins'] ?? 0) + $wins;
            $accumulator[$userId]['per_category'][$categoryId] = [
                'points' => $points,
                'wins' => $wins,
            ];
        }

        return $this->sortAndFormat($accumulator);
    }

    /**
     * @param  array<int, array{total:int, wins:int, per_category: array<int, array{points:int, wins:int}>}>  $accumulator
     * @return list<array<string, mixed>>
     */
    private function sortAndFormat(array $accumulator): array
    {
        $list = [];
        foreach ($accumulator as $userId => $data) {
            $list[] = [
                'user_id' => $userId,
                'total_points' => $data['total'] ?? 0,
                'wins_count' => $data['wins'] ?? 0,
                'per_category' => $data['per_category'] ?? [],
            ];
        }

        usort($list, static fn (array $a, array $b): int => $b['total_points'] <=> $a['total_points']);

        return $list;
    }
}
