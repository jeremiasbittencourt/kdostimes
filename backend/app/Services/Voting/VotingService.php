<?php

declare(strict_types=1);

namespace App\Services\Voting;

use App\Domain\Enums\PresenceStatus;
use App\Domain\Enums\VotingSessionStatus;
use App\Models\PeladaSession;
use App\Models\PeladaVotingSetting;
use App\Models\User;
use App\Models\Vote;
use App\Models\VotingCategory;
use App\Models\VotingSession;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VotingService
{
    public function castVote(
        VotingSession $session,
        User $voter,
        VotingCategory $category,
        int $votedUserId,
    ): Vote {
        $this->assertSessionOpen($session);

        $peladaSession = $session->peladaSession()->firstOrFail();

        $this->assertCategoryEnabledInPelada($peladaSession->pelada_id, $category);
        $this->assertUserWasConfirmed(
            $peladaSession,
            $voter->id,
            'Apenas quem confirmou presença pode votar.',
        );
        $this->assertUserWasConfirmed(
            $peladaSession,
            $votedUserId,
            'Você só pode votar em jogadores que estiveram na pelada.',
        );
        $this->assertNotSelfVote($voter->id, $votedUserId);

        try {
            return DB::transaction(fn (): Vote => Vote::create([
                'voting_session_id' => $session->id,
                'voting_category_id' => $category->id,
                'voter_user_id' => $voter->id,
                'voted_user_id' => $votedUserId,
                'created_at' => now(),
            ]));
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new RuntimeException('Você já votou nesta categoria.');
            }
            throw $e;
        }
    }

    private function assertSessionOpen(VotingSession $session): void
    {
        if ($session->status !== VotingSessionStatus::OPEN) {
            throw new RuntimeException('Esta votação não está aberta.');
        }

        if ($session->closes_at->isPast()) {
            throw new RuntimeException('O prazo de votação já encerrou.');
        }
    }

    private function assertCategoryEnabledInPelada(int $peladaId, VotingCategory $category): void
    {
        $enabled = PeladaVotingSetting::query()
            ->where('pelada_id', $peladaId)
            ->where('voting_category_id', $category->id)
            ->where('enabled', true)
            ->exists();

        if (! $enabled) {
            throw new RuntimeException('Esta categoria não está habilitada na pelada.');
        }
    }

    private function assertUserWasConfirmed(PeladaSession $peladaSession, int $userId, string $errorMessage): void
    {
        $wasConfirmed = $peladaSession->presences()
            ->where('user_id', $userId)
            ->where('status', PresenceStatus::CONFIRMED->value)
            ->exists();

        if (! $wasConfirmed) {
            throw new RuntimeException($errorMessage);
        }
    }

    private function assertNotSelfVote(int $voterId, int $votedUserId): void
    {
        if ($voterId === $votedUserId) {
            throw new RuntimeException('Não é permitido votar em si mesmo.');
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }
}
