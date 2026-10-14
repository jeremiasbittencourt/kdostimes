<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pelada;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pelada>
 */
class PeladaFactory extends Factory
{
    protected $model = Pelada::class;

    public function definition(): array
    {
        return [
            'short_code' => 'PEL-' . strtoupper($this->faker->bothify('?????')),
            'admin_id' => User::factory(),
            'name' => 'Pelada ' . $this->faker->word(),
            'location' => $this->faker->city(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'time' => '20:00',
            'players_per_team' => 5,
            'has_fixed_goalkeepers' => false,
            'confirmation_deadline_hours' => 2,
        ];
    }

    public function withFixedGoalkeepers(): self
    {
        return $this->state(fn () => ['has_fixed_goalkeepers' => true]);
    }
}
