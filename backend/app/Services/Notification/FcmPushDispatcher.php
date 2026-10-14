<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Domain\Notification\PushDispatcher;
use App\Domain\Notification\PushMessage;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

/**
 * Envia via Firebase Cloud Messaging usando kreait/laravel-firebase.
 *
 * Falhas em tokens individuais (ex.: token revogado) são logadas mas não propagadas,
 * para não interromper a entrega em lote.
 */
final class FcmPushDispatcher implements PushDispatcher
{
    public function __construct(
        private readonly Messaging $messaging,
    ) {
    }

    public function send(string $deviceToken, PushMessage $message): void
    {
        $payload = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification(FcmNotification::create($message->title, $message->body))
            ->withData(array_merge(
                ['event' => $message->event->value],
                array_map(static fn ($v): string => (string) $v, $message->data),
            ));

        try {
            $this->messaging->send($payload);
        } catch (MessagingException|Throwable $e) {
            Log::warning('[push:fcm] falha de envio', [
                'event' => $message->event->value,
                'token_prefix' => substr($deviceToken, 0, 8) . '…',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
