<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Enums\PresenceStatus;
use App\Domain\Enums\VotingSessionStatus;
use App\Domain\Notification\PushMessage;
use App\Models\User;
use App\Models\VotingSession;
use App\Services\Notification\NotificationCoordinator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 2h antes de fechar a votação — cutuca quem confirmou presença e ainda não votou em nada.
 */
class SendVotingReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $votingSessionId,
    ) {
    }

    public function handle(NotificationCoordinator $coordinator): void
    {
        $voting = VotingSession::find($this->votingSessionId);
        if ($voting === null || $voting->status !== VotingSessionStatus::OPEN) {
            return;
        }

        $peladaSession = $voting->peladaSession()->first();
        if ($peladaSession === null) {
            return;
        }

        $confirmedUserIds = $peladaSession->presences()
            ->where('status', PresenceStatus::CONFIRMED->value)
            ->pluck('user_id')
            ->all();

        $alreadyVotedIds = $voting->votes()
            ->pluck('voter_user_id')
            ->unique()
            ->all();

        $targetIds = array_values(array_diff($confirmedUserIds, $alreadyVotedIds));
        if ($targetIds === []) {
            return;
        }

        $users = User::whereIn('id', $targetIds)->get();

        $coordinator->broadcast($users, new PushMessage(
            event: NotificationEvent::VOTING_DEADLINE_REMINDER,
            title: 'Última chance de votar!',
            body: 'Faltam poucas horas para fechar a votação da pelada de hoje.',
            data: [
                'voting_session_id' => $voting->id,
                'pelada_session_id' => $peladaSession->id,
                'pelada_id' => $peladaSession->pelada_id,
            ],
        ));
    }
}
