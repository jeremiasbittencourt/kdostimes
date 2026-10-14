<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Enums\PresenceStatus;
use App\Domain\Enums\SessionStatus;
use App\Domain\Notification\PushMessage;
use App\Models\PeladaSession;
use App\Models\User;
use App\Services\Notification\NotificationCoordinator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 2h antes do deadline — lembra quem ainda não respondeu.
 */
class SendPresenceReminderJob implements ShouldQueue
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
        $session = PeladaSession::with('pelada.users')->find($this->peladaSessionId);
        if ($session === null || $session->status !== SessionStatus::OPEN) {
            return;
        }

        $respondedUserIds = $session->presences()
            ->whereIn('status', [PresenceStatus::CONFIRMED->value, PresenceStatus::DECLINED->value])
            ->pluck('user_id')
            ->all();

        $targets = $session->pelada->users
            ->whereNotIn('id', $respondedUserIds);

        if ($targets->isEmpty()) {
            return;
        }

        $message = new PushMessage(
            event: NotificationEvent::PRESENCE_DEADLINE_REMINDER,
            title: 'Cadê você?',
            body: sprintf(
                'Confirme presença antes de %s.',
                $session->deadline_at->format('H:i'),
            ),
            data: [
                'pelada_session_id' => $session->id,
                'pelada_id' => $session->pelada_id,
            ],
        );

        $coordinator->broadcast($targets->all(), $message);
    }
}
