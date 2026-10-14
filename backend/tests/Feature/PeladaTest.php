<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\MemberRole;
use App\Models\Pelada;
use App\Models\User;
use Database\Seeders\VotingCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PeladaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VotingCategoriesSeeder::class);
    }

    #[Test]
    public function it_creates_pelada_and_makes_creator_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/peladas', [
            'name' => 'Fut de Quinta',
            'location' => 'Quadra do Zé',
            'day_of_week' => 4,
            'time' => '20:00',
            'players_per_team' => 5,
            'has_fixed_goalkeepers' => true,
            'confirmation_deadline_hours' => 2,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Fut de Quinta');
        $response->assertJsonPath('data.admin_id', $user->id);

        $pelada = Pelada::first();
        $this->assertTrue($pelada->isAdmin($user));
        $this->assertStringStartsWith('PEL-', $pelada->short_code);
    }

    #[Test]
    public function it_lets_user_join_by_short_code(): void
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->create(['admin_id' => $admin->id]);
        $pelada->members()->create([
            'user_id' => $admin->id,
            'role' => MemberRole::ADMIN->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);

        $newbie = User::factory()->create();

        $response = $this->actingAs($newbie, 'sanctum')->postJson('/api/peladas/join', [
            'short_code' => $pelada->short_code,
        ]);

        $response->assertOk();
        $this->assertTrue($pelada->hasMember($newbie));
    }

    #[Test]
    public function it_forbids_non_members_from_viewing_pelada(): void
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->create(['admin_id' => $admin->id]);
        $pelada->members()->create([
            'user_id' => $admin->id,
            'role' => MemberRole::ADMIN->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);

        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider, 'sanctum')->getJson("/api/peladas/{$pelada->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function only_admin_can_mark_member_as_fixed_goalkeeper(): void
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->withFixedGoalkeepers()->create(['admin_id' => $admin->id]);
        $pelada->members()->create([
            'user_id' => $admin->id,
            'role' => MemberRole::ADMIN->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);
        $member = User::factory()->create();
        $pelada->members()->create([
            'user_id' => $member->id,
            'role' => MemberRole::MEMBER->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($member, 'sanctum')->putJson(
            "/api/peladas/{$pelada->id}/members/{$member->id}",
            ['is_fixed_goalkeeper' => true],
        );
        $response->assertForbidden();

        $response = $this->actingAs($admin, 'sanctum')->putJson(
            "/api/peladas/{$pelada->id}/members/{$member->id}",
            ['is_fixed_goalkeeper' => true],
        );
        $response->assertOk();
    }
}
