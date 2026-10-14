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

class PresenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function member_can_confirm_presence_before_deadline(): void
    {
        [$pelada, $session, $member] = $this->buildPelada();

        $response = $this->actingAs($member, 'sanctum')->postJson(
            "/api/sessions/{$session->id}/presence",
            ['status' => 'confirmed'],
        );

        $response->assertOk();
        $this->assertDatabaseHas('presence_confirmations', [
            'pelada_session_id' => $session->id,
            'user_id' => $member->id,
            'status' => PresenceStatus::CONFIRMED->value,
        ]);
    }

    #[Test]
    public function confirming_twice_updates_the_same_row(): void
    {
        [, $session, $member] = $this->buildPelada();

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/presence", ['status' => 'confirmed']);
        $this->actingAs($member, 'sanctum')
            ->postJson("/api/sessions/{$session->id}/presence", ['status' => 'declined']);

        $this->assertDatabaseCount('presence_confirmations', 1);
        $this->assertDatabaseHas('presence_confirmations', [
            'user_id' => $member->id,
            'status' => PresenceStatus::DECLINED->value,
        ]);
    }

    #[Test]
    public function confirmation_is_blocked_after_deadline(): void
    {
        [$pelada, $session, $member] = $this->buildPelada();
        $session->update(['deadline_at' => now()->subHour()]);

        $response = $this->actingAs($member, 'sanctum')->postJson(
            "/api/sessions/{$session->id}/presence",
            ['status' => 'confirmed'],
        );

        $response->assertForbidden();
    }

    /**
     * @return array{0: Pelada, 1: PeladaSession, 2: User}
     */
    private function buildPelada(): array
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->create(['admin_id' => $admin->id]);
        $member = User::factory()->create();

        foreach ([$admin, $member] as $user) {
            $pelada->members()->create([
                'user_id' => $user->id,
                'role' => $user === $admin ? MemberRole::ADMIN->value : MemberRole::MEMBER->value,
                'is_fixed_goalkeeper' => false,
                'joined_at' => now(),
            ]);
        }

        $session = PeladaSession::factory()->create([
            'pelada_id' => $pelada->id,
            'scheduled_at' => now()->addDay(),
            'deadline_at' => now()->addHours(12),
        ]);

        return [$pelada, $session, $member];
    }
}
