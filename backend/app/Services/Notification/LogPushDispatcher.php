<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Domain\Notification\PushDispatcher;
use App\Domain\Notification\PushMessage;
use Illuminate\Support\Facades\Log;

/**
 * Implementação usada quando PUSH_ENABLED=false (desenvolvimento local).
 * Registra a mensagem no log para que o dev veja o que seria enviado.
 */
final class LogPushDispatcher implements PushDispatcher
{
    public function send(string $deviceToken, PushMessage $message): void
    {
        Log::info('[push:log] would send', [
            'event' => $message->event->value,
            'token_prefix' => substr($deviceToken, 0, 8) . '…',
            'title' => $message->title,
            'body' => $message->body,
            'data' => $message->data,
        ]);
    }
}
