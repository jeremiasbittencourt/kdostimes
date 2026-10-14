<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'skill_rating' => $this->faker->numberBetween(3, 9),
            'avatar_url' => null,
            'email_verified_at' => now(),
        ];
    }

    public function withSkill(int $rating): self
    {
        return $this->state(fn () => ['skill_rating' => $rating]);
    }
}
