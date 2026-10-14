<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Carlos Craque',
            'email' => 'carlos@example.com',
            'password' => 'segredo12',
            'password_confirmation' => 'segredo12',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['user' => ['id', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'carlos@example.com']);
    }

    #[Test]
    public function it_rejects_registration_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Outro',
            'email' => 'dup@example.com',
            'password' => 'segredo12',
            'password_confirmation' => 'segredo12',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function it_logs_in_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'fulano@example.com',
            'password' => bcrypt('senha1234'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'fulano@example.com',
            'password' => 'senha1234',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    #[Test]
    public function it_rejects_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'fulano@example.com',
            'password' => bcrypt('certa'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'fulano@example.com',
            'password' => 'errada',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_returns_me_endpoint_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('id', $user->id);
    }

    #[Test]
    public function it_rejects_me_without_token(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }
}
