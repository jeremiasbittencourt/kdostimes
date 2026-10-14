<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Notification\PushMessage;
use App\Jobs\SendPushNotificationJob;
use App\Models\DeviceToken;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Ponte entre a lógica de negócio e o dispatcher de push.
 *
 * Responsabilidades:
 *  - Respeitar as preferências do usuário (silencia por tipo de evento, quando permitido).
 *  - Eventos não-silenciáveis (ex.: remoção do grupo) sempre enviam.
 *  - Despachar um job por device token (cada job chama o PushDispatcher).
 */
class NotificationCoordinator
{
    /**
     * @param  iterable<User>  $users
     */
    public function broadcast(iterable $users, PushMessage $message): void
    {
        $targetUsers = $this->filterByPreferences($users, $message->event);

        if ($targetUsers->isEmpty()) {
            return;
        }

        $tokens = DeviceToken::query()
            ->whereIn('user_id', $targetUsers->pluck('id'))
            ->pluck('token')
            ->all();

        foreach ($tokens as $token) {
            SendPushNotificationJob::dispatch($token, $message);
        }
    }

    public function send(User $user, PushMessage $message): void
    {
        $this->broadcast([$user], $message);
    }

    /**
     * @param  iterable<User>  $users
     * @return Collection<int, User>
     */
    private function filterByPreferences(iterable $users, NotificationEvent $event): Collection
    {
        $collection = $users instanceof Collection ? $users : collect($users);

        if (! $event->isSilenceable()) {
            return $collection;
        }

        $userIds = $collection->pluck('id')->all();
        if ($userIds === []) {
            return $collection;
        }

        $silenced = NotificationPreference::query()
            ->whereIn('user_id', $userIds)
            ->where('event_type', $event->value)
            ->where('enabled', false)
            ->pluck('user_id')
            ->all();

        if ($silenced === []) {
            return $collection;
        }

        $silencedSet = array_flip($silenced);

        return $collection->reject(fn (User $user): bool => isset($silencedSet[$user->id]))->values();
    }
}
