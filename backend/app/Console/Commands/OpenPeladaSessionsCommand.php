<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Enums\SessionStatus;
use App\Domain\Notification\PushMessage;
use App\Jobs\ClosePresenceJob;
use App\Jobs\OpenVotingJob;
use App\Jobs\SendPresenceReminderJob;
use App\Models\Pelada;
use App\Models\PeladaSession;
use App\Services\Notification\NotificationCoordinator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Para cada pelada, garante que existe PeladaSession para a PRÓXIMA ocorrência
 * (baseada em day_of_week + time) dentro dos próximos 7 dias, e agenda os jobs
 * de ciclo de vida: reminder de presença, fechamento e abertura de votação.
 *
 * Pensado para rodar diariamente via Laravel Scheduler.
 */
class OpenPeladaSessionsCommand extends Command
{
    protected $signature = 'peladas:open-sessions';

    protected $description = 'Cria a próxima PeladaSession de cada pelada e agenda os jobs de ciclo de vida.';

    public function handle(NotificationCoordinator $coordinator): int
    {
        $peladas = Pelada::with('users')->get();
        $created = 0;

        foreach ($peladas as $pelada) {
            $scheduledAt = $this->nextOccurrence(
                (int) $pelada->day_of_week,
                (string) $pelada->time,
            );
            $deadlineAt = $scheduledAt->subHours((int) $pelada->confirmation_deadline_hours);

            $exists = PeladaSession::query()
                ->where('pelada_id', $pelada->id)
                ->where('scheduled_at', $scheduledAt)
                ->exists();

            if ($exists) {
                continue;
            }

            $session = PeladaSession::create([
                'pelada_id' => $pelada->id,
                'scheduled_at' => $scheduledAt,
                'deadline_at' => $deadlineAt,
                'status' => SessionStatus::OPEN->value,
            ]);
            $created++;

            $this->scheduleLifecycleJobs($session, $pelada, $coordinator, $scheduledAt, $deadlineAt);
        }

        $this->info("Sessões criadas: {$created}");

        return self::SUCCESS;
    }

    private function nextOccurrence(int $dayOfWeek, string $time): CarbonImmutable
    {
        $now = CarbonImmutable::now();
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        $candidate = $now
            ->setTime((int) $hour, (int) $minute, 0);

        while ((int) $candidate->dayOfWeek !== $dayOfWeek || $candidate->isPast()) {
            $candidate = $candidate->addDay();
        }

        return $candidate;
    }

    private function scheduleLifecycleJobs(
        PeladaSession $session,
        Pelada $pelada,
        NotificationCoordinator $coordinator,
        CarbonImmutable $scheduledAt,
        CarbonImmutable $deadlineAt,
    ): void {
        if ($deadlineAt->isFuture()) {
            ClosePresenceJob::dispatch($session->id)->delay($deadlineAt);
        } else {
            ClosePresenceJob::dispatch($session->id);
        }

        $reminderHours = (int) config('cade-os-times.presence.reminder_hours_before_deadline', 2);
        $reminderAt = $deadlineAt->subHours($reminderHours);
        if ($reminderAt->isFuture()) {
            SendPresenceReminderJob::dispatch($session->id)->delay($reminderAt);
        }

        $matchDurationHours = (int) config('cade-os-times.pelada.match_duration_hours', 2);
        $votingAt = $scheduledAt->addHours($matchDurationHours);
        OpenVotingJob::dispatch($session->id)->delay($votingAt);

        $coordinator->broadcast($pelada->users, new PushMessage(
            event: NotificationEvent::SESSION_OPENED,
            title: sprintf('Pelada %s está marcada!', $pelada->name),
            body: sprintf(
                '%s às %s — confirme presença até %s.',
                $scheduledAt->translatedFormat('l, d/m'),
                $scheduledAt->format('H:i'),
                $deadlineAt->format('H:i'),
            ),
            data: [
                'pelada_session_id' => $session->id,
                'pelada_id' => $pelada->id,
            ],
        ));
    }
}
