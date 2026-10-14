<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Ranking;

use App\Domain\Ranking\DTO\VoteTally;
use App\Domain\Ranking\RankingComputer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RankingComputerTest extends TestCase
{
    private const CAT_CRAQUE = 1;
    private const CAT_GOLEADOR = 2;
    private const CAT_PIOR = 3;

    #[Test]
    public function it_scores_positive_votes_one_point_each(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->craque(voter: 10, voted: 1),
            $this->craque(voter: 11, voted: 2),
            $this->craque(voter: 12, voted: 2),
        ]);

        $this->assertArrayHasKey(1, $stats);
        $this->assertArrayHasKey(2, $stats);

        $this->assertSame(1, $stats[1]->pointsByCategory[self::CAT_CRAQUE] ?? 0);
    }

    #[Test]
    public function it_awards_winner_bonus_for_most_voted_in_positive_category(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->craque(voter: 10, voted: 1),
            $this->craque(voter: 11, voted: 1),
            $this->craque(voter: 12, voted: 1),
            $this->craque(voter: 13, voted: 2),
        ]);

        $this->assertSame(
            6,
            $stats[1]->pointsByCategory[self::CAT_CRAQUE],
            'Vencedor: 3 votos (+3) + bônus (+3) = 6.',
        );
        $this->assertSame(
            1,
            $stats[2]->pointsByCategory[self::CAT_CRAQUE],
            'Segundo colocado: 1 voto, sem bônus.',
        );
        $this->assertTrue($stats[1]->winsByCategory[self::CAT_CRAQUE]);
        $this->assertFalse($stats[2]->winsByCategory[self::CAT_CRAQUE]);
    }

    #[Test]
    public function it_gives_bonus_to_all_tied_winners(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->craque(voter: 10, voted: 1),
            $this->craque(voter: 11, voted: 1),
            $this->craque(voter: 12, voted: 2),
            $this->craque(voter: 13, voted: 2),
        ]);

        $this->assertSame(5, $stats[1]->pointsByCategory[self::CAT_CRAQUE]);
        $this->assertSame(5, $stats[2]->pointsByCategory[self::CAT_CRAQUE]);
        $this->assertTrue($stats[1]->winsByCategory[self::CAT_CRAQUE]);
        $this->assertTrue($stats[2]->winsByCategory[self::CAT_CRAQUE]);
    }

    #[Test]
    public function it_scores_humor_votes_as_negative_without_bonus(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->pior(voter: 10, voted: 1),
            $this->pior(voter: 11, voted: 1),
            $this->pior(voter: 12, voted: 1),
            $this->pior(voter: 13, voted: 2),
        ]);

        $this->assertSame(-3, $stats[1]->pointsByCategory[self::CAT_PIOR]);
        $this->assertSame(-1, $stats[2]->pointsByCategory[self::CAT_PIOR]);

        $this->assertFalse($stats[1]->winsByCategory[self::CAT_PIOR] ?? false);
        $this->assertFalse($stats[2]->winsByCategory[self::CAT_PIOR] ?? false);
    }

    #[Test]
    public function it_sums_points_across_multiple_categories_per_user(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->craque(voter: 10, voted: 1),
            $this->craque(voter: 11, voted: 1),
            $this->craque(voter: 12, voted: 1),
            $this->goleador(voter: 10, voted: 1),
            $this->goleador(voter: 11, voted: 1),
            $this->pior(voter: 13, voted: 1),
        ]);

        $this->assertSame(6, $stats[1]->pointsByCategory[self::CAT_CRAQUE]);
        $this->assertSame(5, $stats[1]->pointsByCategory[self::CAT_GOLEADOR]);
        $this->assertSame(-1, $stats[1]->pointsByCategory[self::CAT_PIOR]);
        $this->assertSame(10, $stats[1]->totalPoints);
    }

    #[Test]
    public function it_produces_empty_result_when_no_votes(): void
    {
        $computer = new RankingComputer();

        $this->assertSame([], $computer->compute([]));
    }

    #[Test]
    public function it_does_not_count_category_with_zero_votes(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->craque(voter: 10, voted: 1),
        ]);

        $this->assertArrayNotHasKey(self::CAT_GOLEADOR, $stats[1]->pointsByCategory);
    }

    #[Test]
    public function it_counts_votes_received_per_category(): void
    {
        $computer = new RankingComputer();

        $stats = $computer->compute([
            $this->craque(voter: 10, voted: 1),
            $this->craque(voter: 11, voted: 1),
            $this->craque(voter: 12, voted: 1),
            $this->goleador(voter: 10, voted: 1),
        ]);

        $this->assertSame(3, $stats[1]->votesReceivedByCategory[self::CAT_CRAQUE]);
        $this->assertSame(1, $stats[1]->votesReceivedByCategory[self::CAT_GOLEADOR]);
    }

    private function craque(int $voter, int $voted): VoteTally
    {
        return new VoteTally(
            categoryId: self::CAT_CRAQUE,
            pointsPerVote: 1,
            bonusForWinner: 3,
            isPositive: true,
            votedUserId: $voted,
        );
    }

    private function goleador(int $voter, int $voted): VoteTally
    {
        return new VoteTally(
            categoryId: self::CAT_GOLEADOR,
            pointsPerVote: 1,
            bonusForWinner: 3,
            isPositive: true,
            votedUserId: $voted,
        );
    }

    private function pior(int $voter, int $voted): VoteTally
    {
        return new VoteTally(
            categoryId: self::CAT_PIOR,
            pointsPerVote: -1,
            bonusForWinner: 0,
            isPositive: false,
            votedUserId: $voted,
        );
    }
}
