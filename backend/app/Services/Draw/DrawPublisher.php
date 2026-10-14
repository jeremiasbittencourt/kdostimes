<?php

declare(strict_types=1);

namespace App\Services\Draw;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Enums\PresenceStatus;
use App\Domain\Notification\PushMessage;
use App\Models\Draw;
use App\Models\User;
use App\Services\Notification\NotificationCoordinator;
use Illuminate\Support\Facades\DB;

class DrawPublisher
{
    public function __construct(
        private readonly NotificationCoordinator $coordinator,
    ) {
    }

    public function publish(Draw $draw): Draw
    {
        $published = DB::transaction(function () use ($draw): Draw {
            Draw::where('pelada_session_id', $draw->pelada_session_id)
                ->where('id', '!=', $draw->id)
                ->update(['published' => false]);

            $draw->update([
                'published' => true,
                'published_at' => now(),
            ]);

            return $draw->fresh(['teams.players.user']);
        });

        $this->notifyConfirmedUsers($published);

        return $published;
    }

    private function notifyConfirmedUsers(Draw $draw): void
    {
        $session = $draw->session()->first();
        if ($session === null) {
            return;
        }

        $userIds = $session->presences()
            ->where('status', PresenceStatus::CONFIRMED->value)
            ->pluck('user_id')
            ->all();

        if ($userIds === []) {
            return;
        }

        $users = User::whereIn('id', $userIds)->get();

        $this->coordinator->broadcast($users, new PushMessage(
            event: NotificationEvent::DRAW_PUBLISHED,
            title: 'Cadê Os Times? Já saiu!',
            body: 'O sorteio da pelada foi publicado — confere agora.',
            data: [
                'draw_id' => $draw->id,
                'pelada_session_id' => $draw->pelada_session_id,
                'pelada_id' => $session->pelada_id,
            ],
        ));
    }
}
