<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voting\CastVoteRequest;
use App\Http\Resources\VotingSessionResource;
use App\Models\PeladaSession;
use App\Models\Vote;
use App\Models\VotingCategory;
use App\Models\VotingSession;
use App\Services\Voting\VotingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class VotingController extends Controller
{
    public function __construct(
        private readonly VotingService $voting,
    ) {
    }

    public function show(Request $request, PeladaSession $session): JsonResponse
    {
        $pelada = $session->pelada()->firstOrFail();
        $this->authorize('view', $pelada);

        $voting = $session->votingSession()->first();
        if ($voting === null) {
            return response()->json(['voting' => null]);
        }

        $enabledCategoryIds = $pelada->votingSettings()
            ->where('enabled', true)
            ->pluck('voting_category_id')
            ->all();

        $categories = VotingCategory::whereIn('id', $enabledCategoryIds)->get();
        $voting->setRelation('categories', $categories);

        $myVotes = Vote::query()
            ->where('voting_session_id', $voting->id)
            ->where('voter_user_id', $request->user()->id)
            ->get(['voting_category_id', 'voted_user_id'])
            ->map(fn (Vote $v): array => [
                'category_id' => $v->voting_category_id,
                'voted_user_id' => $v->voted_user_id,
            ])
            ->all();
        $voting->setAttribute('my_votes', $myVotes);

        return response()->json([
            'voting' => new VotingSessionResource($voting),
        ]);
    }

    public function castVote(CastVoteRequest $request, VotingSession $votingSession): JsonResponse
    {
        $peladaSession = $votingSession->peladaSession()->firstOrFail();
        $pelada = $peladaSession->pelada()->firstOrFail();
        $this->authorize('view', $pelada);

        $category = VotingCategory::findOrFail($request->integer('category_id'));

        try {
            $this->voting->castVote(
                session: $votingSession,
                voter: $request->user(),
                category: $category,
                votedUserId: $request->integer('voted_user_id'),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Voto registrado.'], 201);
    }

    public function results(Request $request, VotingSession $votingSession): JsonResponse
    {
        $peladaSession = $votingSession->peladaSession()->firstOrFail();
        $pelada = $peladaSession->pelada()->firstOrFail();
        $this->authorize('view', $pelada);

        if ($votingSession->closes_at->isFuture() && $votingSession->status->value === 'open') {
            return response()->json([
                'message' => 'Resultados só ficam visíveis após o fechamento da votação.',
            ], 403);
        }

        $rows = $votingSession->votes()
            ->select('voting_category_id', 'voted_user_id')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('voting_category_id', 'voted_user_id')
            ->get();

        $byCategory = [];
        foreach ($rows as $row) {
            $byCategory[$row->voting_category_id][] = [
                'voted_user_id' => (int) $row->voted_user_id,
                'total' => (int) $row->total,
            ];
        }

        foreach ($byCategory as $categoryId => $tally) {
            usort($byCategory[$categoryId], static fn (array $a, array $b): int => $b['total'] <=> $a['total']);
        }

        return response()->json(['results' => $byCategory]);
    }
}
