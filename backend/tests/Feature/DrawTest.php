<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\MemberRole;
use App\Domain\Enums\PresenceStatus;
use App\Models\Pelada;
use App\Models\PeladaSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrawTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_generate_draw_with_confirmed_players(): void
    {
        [$admin, $session] = $this->setupSessionWithConfirmed(8);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/draw");

        $response->assertOk();
        $response->assertJsonPath('data.published', false);
        $response->assertJsonStructure(['data' => ['teams' => [['name', 'total_rating', 'players']]]]);
    }

    #[Test]
    public function non_admin_cannot_generate_draw(): void
    {
        [, $session, $members] = $this->setupSessionWithConfirmedReturningAll(6);
        $outsider = $members[1];

        $response = $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/draw");

        $response->assertForbidden();
    }

    #[Test]
    public function publishing_replaces_previously_published(): void
    {
        [$admin, $session] = $this->setupSessionWithConfirmed(6);

        $firstResp = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/draw");
        $firstDrawId = $firstResp->json('data.id');
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/draws/{$firstDrawId}/publish")
            ->assertOk();

        $secondResp = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/draw");
        $secondDrawId = $secondResp->json('data.id');
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/draws/{$secondDrawId}/publish")
            ->assertOk();

        $this->assertDatabaseHas('draws', ['id' => $secondDrawId, 'published' => true]);
        $this->assertDatabaseHas('draws', ['id' => $firstDrawId, 'published' => false]);
    }

    #[Test]
    public function refusing_draw_with_less_than_two_players_returns_422(): void
    {
        [$admin, $session] = $this->setupSessionWithConfirmed(1);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/draw");

        $response->assertStatus(422);
    }

    /**
     * @return array{0: User, 1: PeladaSession}
     */
    private function setupSessionWithConfirmed(int $playerCount): array
    {
        [$admin, $session] = $this->setupSessionWithConfirmedReturningAll($playerCount);

        return [$admin, $session];
    }

    /**
     * @return array{0: User, 1: PeladaSession, 2: list<User>}
     */
    private function setupSessionWithConfirmedReturningAll(int $playerCount): array
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->create(['admin_id' => $admin->id]);
        $pelada->members()->create([
            'user_id' => $admin->id,
            'role' => MemberRole::ADMIN->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);

        $session = PeladaSession::factory()->create([
            'pelada_id' => $pelada->id,
        ]);

        $members = [];
        for ($i = 0; $i < $playerCount; $i++) {
            $u = User::factory()->create(['skill_rating' => ($i % 10) + 1]);
            $pelada->members()->create([
                'user_id' => $u->id,
                'role' => MemberRole::MEMBER->value,
                'is_fixed_goalkeeper' => false,
                'joined_at' => now(),
            ]);
            $session->presences()->create([
                'user_id' => $u->id,
                'status' => PresenceStatus::CONFIRMED->value,
                'confirmed_at' => now(),
            ]);
            $members[] = $u;
        }

        return [$admin, $session, $members];
    }
}
