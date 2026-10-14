<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Enums\PresenceStatus;
use App\Domain\Enums\VotingSessionStatus;
use App\Domain\Notification\PushMessage;
use App\Models\PeladaSession;
use App\Models\User;
use App\Models\VotingSession;
use App\Services\Notification\NotificationCoordinator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ao fim da pelada: cria a voting_session, abre a janela de 24h,
 * agenda CloseVotingAndComputeRankingJob e SendVotingReminderJob,
 * e dispara push "votação aberta".
 */
class OpenVotingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $peladaSessionId,
    ) {
    }

    public function handle(NotificationCoordinator $coordinator): void
    {
        $peladaSession = PeladaSession::find($this->peladaSessionId);
        if ($peladaSession === null) {
            return;
        }

        if ($peladaSession->votingSession()->exists()) {
            return;
        }

        $windowHours = (int) config('cade-os-times.voting.window_hours', 24);
        $opensAt = now();
        $closesAt = $opensAt->copy()->addHours($windowHours);

        $voting = VotingSession::create([
            'pelada_session_id' => $peladaSession->id,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'status' => VotingSessionStatus::OPEN->value,
        ]);

        CloseVotingAndComputeRankingJob::dispatch($voting->id)->delay($closesAt);

        $reminderOffset = (int) config('cade-os-times.voting.reminder_hours_before_close', 2);
        $reminderAt = $closesAt->copy()->subHours($reminderOffset);
        if ($reminderAt->isFuture()) {
            SendVotingReminderJob::dispatch($voting->id)->delay($reminderAt);
        }

        $confirmedUserIds = $peladaSession->presences()
            ->where('status', PresenceStatus::CONFIRMED->value)
            ->pluck('user_id')
            ->all();

        if ($confirmedUserIds === []) {
            return;
        }

        $users = User::whereIn('id', $confirmedUserIds)->get();

        $coordinator->broadcast($users, new PushMessage(
            event: NotificationEvent::VOTING_OPENED,
            title: 'Hora de eleger o craque da pelada',
            body: 'Votação aberta por 24h. Bora escolher os destaques!',
            data: [
                'voting_session_id' => $voting->id,
                'pelada_session_id' => $peladaSession->id,
                'pelada_id' => $peladaSession->pelada_id,
            ],
        ));
    }
}
