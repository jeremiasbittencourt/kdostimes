<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Enums\SessionStatus;
use App\Models\Pelada;
use App\Models\PeladaSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeladaSession>
 */
class PeladaSessionFactory extends Factory
{
    protected $model = PeladaSession::class;

    public function definition(): array
    {
        $scheduled = now()->addDay();

        return [
            'pelada_id' => Pelada::factory(),
            'scheduled_at' => $scheduled,
            'deadline_at' => $scheduled->copy()->subHours(2),
            'status' => SessionStatus::OPEN->value,
        ];
    }

    public function closed(): self
    {
        return $this->state(fn () => [
            'status' => SessionStatus::CLOSED->value,
            'scheduled_at' => now()->subHour(),
            'deadline_at' => now()->subHours(3),
        ]);
    }
}
