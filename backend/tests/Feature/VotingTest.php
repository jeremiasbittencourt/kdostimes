<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\MemberRole;
use App\Domain\Enums\PresenceStatus;
use App\Domain\Enums\VotingSessionStatus;
use App\Models\Pelada;
use App\Models\PeladaSession;
use App\Models\PeladaVotingSetting;
use App\Models\User;
use App\Models\VotingCategory;
use App\Models\VotingSession;
use Database\Seeders\VotingCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VotingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VotingCategoriesSeeder::class);
    }

    #[Test]
    public function confirmed_voter_can_cast_vote(): void
    {
        [$voter, $target, $votingSession, $category] = $this->setupVoting();

        $response = $this->actingAs($voter, 'sanctum')->postJson(
            "/api/voting-sessions/{$votingSession->id}/votes",
            ['category_id' => $category->id, 'voted_user_id' => $target->id],
        );

        $response->assertCreated();
        $this->assertDatabaseHas('votes', [
            'voting_session_id' => $votingSession->id,
            'voting_category_id' => $category->id,
            'voter_user_id' => $voter->id,
            'voted_user_id' => $target->id,
        ]);
    }

    #[Test]
    public function user_cannot_vote_twice_in_the_same_category(): void
    {
        [$voter, $target, $votingSession, $category] = $this->setupVoting();

        $this->actingAs($voter, 'sanctum')->postJson(
            "/api/voting-sessions/{$votingSession->id}/votes",
            ['category_id' => $category->id, 'voted_user_id' => $target->id],
        )->assertCreated();

        $response = $this->actingAs($voter, 'sanctum')->postJson(
            "/api/voting-sessions/{$votingSession->id}/votes",
            ['category_id' => $category->id, 'voted_user_id' => $target->id],
        );

        $response->assertStatus(422);
        $this->assertDatabaseCount('votes', 1);
    }

    #[Test]
    public function user_cannot_vote_for_self(): void
    {
        [$voter, , $votingSession, $category] = $this->setupVoting();

        $response = $this->actingAs($voter, 'sanctum')->postJson(
            "/api/voting-sessions/{$votingSession->id}/votes",
            ['category_id' => $category->id, 'voted_user_id' => $voter->id],
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function non_confirmed_user_cannot_vote(): void
    {
        [, $target, $votingSession, $category, $pelada] = $this->setupVoting();

        $outsider = User::factory()->create();
        $pelada->members()->create([
            'user_id' => $outsider->id,
            'role' => MemberRole::MEMBER->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($outsider, 'sanctum')->postJson(
            "/api/voting-sessions/{$votingSession->id}/votes",
            ['category_id' => $category->id, 'voted_user_id' => $target->id],
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function disabled_category_in_pelada_blocks_vote(): void
    {
        [$voter, $target, $votingSession, $category, $pelada] = $this->setupVoting();

        PeladaVotingSetting::where('pelada_id', $pelada->id)
            ->where('voting_category_id', $category->id)
            ->update(['enabled' => false]);

        $response = $this->actingAs($voter, 'sanctum')->postJson(
            "/api/voting-sessions/{$votingSession->id}/votes",
            ['category_id' => $category->id, 'voted_user_id' => $target->id],
        );

        $response->assertStatus(422);
    }

    /**
     * @return array{0: User, 1: User, 2: VotingSession, 3: VotingCategory, 4: Pelada}
     */
    private function setupVoting(): array
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->create(['admin_id' => $admin->id]);
        $pelada->members()->create([
            'user_id' => $admin->id,
            'role' => MemberRole::ADMIN->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);

        $voter = User::factory()->create();
        $target = User::factory()->create();
        foreach ([$voter, $target] as $user) {
            $pelada->members()->create([
                'user_id' => $user->id,
                'role' => MemberRole::MEMBER->value,
                'is_fixed_goalkeeper' => false,
                'joined_at' => now(),
            ]);
        }

        $session = PeladaSession::factory()->closed()->create(['pelada_id' => $pelada->id]);
        foreach ([$voter, $target] as $user) {
            $session->presences()->create([
                'user_id' => $user->id,
                'status' => PresenceStatus::CONFIRMED->value,
                'confirmed_at' => now(),
            ]);
        }

        $votingSession = VotingSession::create([
            'pelada_session_id' => $session->id,
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHours(23),
            'status' => VotingSessionStatus::OPEN->value,
        ]);

        $category = VotingCategory::where('code', 'craque')->firstOrFail();

        PeladaVotingSetting::firstOrCreate(
            ['pelada_id' => $pelada->id, 'voting_category_id' => $category->id],
            ['enabled' => true],
        );

        return [$voter, $target, $votingSession, $category, $pelada];
    }
}
