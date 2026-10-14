<?php

declare(strict_types=1);

namespace App\Services\Ranking;

use App\Domain\Ranking\DTO\VoteTally;
use App\Domain\Ranking\RankingComputer;
use App\Models\PeladaSession;
use App\Models\RankingEntry;
use App\Models\VotingSession;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function __construct(
        private readonly RankingComputer $computer,
    ) {
    }

    public function recomputeForVotingSession(VotingSession $session): void
    {
        $peladaSession = PeladaSession::query()
            ->where('id', $session->pelada_session_id)
            ->firstOrFail();
        $peladaId = $peladaSession->pelada_id;

        $votes = DB::table('votes')
            ->join('voting_categories', 'voting_categories.id', '=', 'votes.voting_category_id')
            ->where('votes.voting_session_id', $session->id)
            ->select([
                'votes.voting_category_id as categoryId',
                'votes.voted_user_id as votedUserId',
                'voting_categories.points_per_vote as pointsPerVote',
                'voting_categories.bonus_for_winner as bonusForWinner',
                'voting_categories.type as type',
            ])
            ->get();

        $tallies = $votes->map(fn ($row): VoteTally => new VoteTally(
            categoryId: (int) $row->categoryId,
            pointsPerVote: (int) $row->pointsPerVote,
            bonusForWinner: (int) $row->bonusForWinner,
            isPositive: $row->type === 'positive',
            votedUserId: (int) $row->votedUserId,
        ))->all();

        $stats = $this->computer->compute($tallies);

        DB::transaction(function () use ($stats, $peladaId): void {
            foreach ($stats as $userId => $userStats) {
                foreach ($userStats->pointsByCategory as $categoryId => $points) {
                    $entry = RankingEntry::firstOrNew([
                        'pelada_id' => $peladaId,
                        'user_id' => $userId,
                        'voting_category_id' => $categoryId,
                    ]);
                    $entry->total_points = (int) ($entry->total_points ?? 0) + $points;
                    if (($userStats->winsByCategory[$categoryId] ?? false) === true) {
                        $entry->wins_count = (int) ($entry->wins_count ?? 0) + 1;
                    }
                    $entry->last_computed_at = now();
                    $entry->save();
                }
            }
        });
    }
}
