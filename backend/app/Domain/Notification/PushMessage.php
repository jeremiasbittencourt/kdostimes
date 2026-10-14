<?php

declare(strict_types=1);

namespace App\Domain\Notification;

use App\Domain\Enums\NotificationEvent;

final readonly class PushMessage
{
    /**
     * @param  array<string, scalar>  $data   payload adicional (IDs, não dados sensíveis)
     */
    public function __construct(
        public NotificationEvent $event,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
    }
}
