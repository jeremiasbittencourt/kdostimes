<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Enums\PresenceStatus;
use App\Domain\Enums\SessionStatus;
use App\Domain\Enums\VotingSessionStatus;
use App\Domain\Notification\PushMessage;
use App\Models\User;
use App\Models\VotingSession;
use App\Services\Notification\NotificationCoordinator;
use App\Services\Ranking\RankingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fecha a sessão de votação, computa ranking (atualiza ranking_entries)
 * e dispara push "ranking atualizado". Transiciona a pelada_session para FINISHED.
 */
class CloseVotingAndComputeRankingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $votingSessionId,
    ) {
    }

    public function handle(RankingService $ranking, NotificationCoordinator $coordinator): void
    {
        $voting = VotingSession::find($this->votingSessionId);
        if ($voting === null || $voting->status === VotingSessionStatus::CLOSED) {
            return;
        }

        $voting->update(['status' => VotingSessionStatus::CLOSED->value]);

        $ranking->recomputeForVotingSession($voting);

        $peladaSession = $voting->peladaSession()->first();
        if ($peladaSession !== null && $peladaSession->status !== SessionStatus::FINISHED) {
            $peladaSession->update(['status' => SessionStatus::FINISHED->value]);
        }

        $confirmedUserIds = $peladaSession?->presences()
            ->where('status', PresenceStatus::CONFIRMED->value)
            ->pluck('user_id')
            ->all() ?? [];

        if ($confirmedUserIds === []) {
            return;
        }

        $users = User::whereIn('id', $confirmedUserIds)->get();

        $coordinator->broadcast($users, new PushMessage(
            event: NotificationEvent::VOTING_CLOSED,
            title: 'Ranking atualizado!',
            body: 'Confira quem foi o craque da pelada.',
            data: [
                'voting_session_id' => $voting->id,
                'pelada_session_id' => $peladaSession?->id ?? 0,
                'pelada_id' => $peladaSession?->pelada_id ?? 0,
            ],
        ));
    }
}
