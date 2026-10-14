<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Enums\MemberRole;
use App\Models\Pelada;
use App\Models\RankingEntry;
use App\Models\User;
use App\Models\VotingCategory;
use Database\Seeders\VotingCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VotingCategoriesSeeder::class);
    }

    #[Test]
    public function it_returns_ranking_all_time_from_materialized_entries(): void
    {
        [$admin, $pelada, $u1, $u2] = $this->setupPeladaWithTwoMembers();
        $craque = VotingCategory::where('code', 'craque')->firstOrFail();

        RankingEntry::create([
            'pelada_id' => $pelada->id,
            'user_id' => $u1->id,
            'voting_category_id' => $craque->id,
            'total_points' => 10,
            'wins_count' => 2,
            'last_computed_at' => now(),
        ]);
        RankingEntry::create([
            'pelada_id' => $pelada->id,
            'user_id' => $u2->id,
            'voting_category_id' => $craque->id,
            'total_points' => 3,
            'wins_count' => 0,
            'last_computed_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/peladas/{$pelada->id}/ranking?period=all");

        $response->assertOk();
        $response->assertJsonPath('ranking.0.user_id', $u1->id);
        $response->assertJsonPath('ranking.0.total_points', 10);
        $response->assertJsonPath('ranking.1.user_id', $u2->id);
    }

    #[Test]
    public function it_forbids_non_member_from_reading_ranking(): void
    {
        [, $pelada] = $this->setupPeladaWithTwoMembers();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/peladas/{$pelada->id}/ranking?period=all");

        $response->assertForbidden();
    }

    #[Test]
    public function unknown_period_falls_back_to_all(): void
    {
        [$admin, $pelada] = $this->setupPeladaWithTwoMembers();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/peladas/{$pelada->id}/ranking?period=bananinha");

        $response->assertOk();
        $response->assertJsonPath('period', 'all');
    }

    /**
     * @return array{0: User, 1: Pelada, 2: User, 3: User}
     */
    private function setupPeladaWithTwoMembers(): array
    {
        $admin = User::factory()->create();
        $pelada = Pelada::factory()->create(['admin_id' => $admin->id]);
        $pelada->members()->create([
            'user_id' => $admin->id,
            'role' => MemberRole::ADMIN->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        foreach ([$u1, $u2] as $u) {
            $pelada->members()->create([
                'user_id' => $u->id,
                'role' => MemberRole::MEMBER->value,
                'is_fixed_goalkeeper' => false,
                'joined_at' => now(),
            ]);
        }

        return [$admin, $pelada, $u1, $u2];
    }
}
