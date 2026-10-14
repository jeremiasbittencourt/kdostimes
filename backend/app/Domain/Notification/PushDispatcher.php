<?php

declare(strict_types=1);

namespace App\Domain\Notification;

interface PushDispatcher
{
    /**
     * Envia a mensagem para o device token informado.
     * Implementações devem ser resilientes — falhas individuais de token não devem
     * interromper outras entregas.
     */
    public function send(string $deviceToken, PushMessage $message): void;
}
