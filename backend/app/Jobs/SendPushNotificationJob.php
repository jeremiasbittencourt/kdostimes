<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Notification\PushDispatcher;
use App\Domain\Notification\PushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $deviceToken,
        public readonly PushMessage $message,
    ) {
    }

    public function handle(PushDispatcher $dispatcher): void
    {
        $dispatcher->send($this->deviceToken, $this->message);
    }
}
