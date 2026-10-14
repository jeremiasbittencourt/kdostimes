<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Enums\SessionStatus;
use App\Models\PeladaSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fecha a lista de presença quando o deadline chega.
 * Disparado com delay igual a (deadline_at - agora) quando a sessão é criada.
 */
class ClosePresenceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $peladaSessionId,
    ) {
    }

    public function handle(): void
    {
        $session = PeladaSession::find($this->peladaSessionId);
        if ($session === null || $session->status !== SessionStatus::OPEN) {
            return;
        }

        $session->update(['status' => SessionStatus::CLOSED->value]);
    }
}
