<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\TeamDraw;

use App\Domain\Enums\Position;
use App\Domain\TeamDraw\DTO\AssignedPlayer;
use App\Domain\TeamDraw\DTO\DrawInput;
use App\Domain\TeamDraw\DTO\DrawResult;
use App\Domain\TeamDraw\DTO\PlayerInput;
use App\Domain\TeamDraw\DTO\TeamComposition;
use App\Domain\TeamDraw\Strategies\SnakeDraftStrategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SnakeDraftStrategyTest extends TestCase
{
    #[Test]
    public function it_forms_two_balanced_teams_with_ten_line_players(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [];
        for ($i = 1; $i <= 10; $i++) {
            $players[] = new PlayerInput(
                userId: $i,
                skill: $i,
                primaryPosition: Position::MEI,
                secondaryPosition: null,
                isFixedGoalkeeper: false,
            );
        }

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 5,
            hasFixedGoalkeepers: false,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertCount(2, $result->teams);
        $this->assertSame(5, $result->teams[0]->lineSize());
        $this->assertSame(5, $result->teams[1]->lineSize());

        $delta = abs($result->teams[0]->totalRating() - $result->teams[1]->totalRating());
        $this->assertLessThanOrEqual(2, $delta, 'Soma de estrelas deve respeitar a tolerância de ±2.');

        $this->assertSame([], $result->warnings);
        $this->assertNoDuplicateUsers($result);
    }

    #[Test]
    public function it_distributes_exactly_one_goalkeeper_per_team_when_count_matches(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [
            ...$this->makeLinePool(10),
            new PlayerInput(101, 8, Position::GOL, null, true),
            new PlayerInput(102, 7, Position::GOL, null, true),
        ];

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 5,
            hasFixedGoalkeepers: true,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertCount(2, $result->teams);
        foreach ($result->teams as $team) {
            $goalkeepers = array_filter(
                $team->players,
                static fn (AssignedPlayer $p): bool => $p->isGoalkeeper,
            );
            $this->assertCount(1, $goalkeepers, 'Cada time deve ter exatamente 1 goleiro fixo.');
        }

        $this->assertGoalkeepersNotCountedInTotalRating($result);
    }

    #[Test]
    public function it_warns_and_distributes_partial_goalkeepers_when_fewer_than_teams(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [
            ...$this->makeLinePool(15),
            new PlayerInput(101, 6, Position::GOL, null, true),
        ];

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 5,
            hasFixedGoalkeepers: true,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertCount(3, $result->teams);

        $teamsWithGk = 0;
        foreach ($result->teams as $team) {
            foreach ($team->players as $p) {
                if ($p->isGoalkeeper) {
                    $teamsWithGk++;
                }
            }
        }
        $this->assertSame(1, $teamsWithGk);

        $this->assertNotEmpty($result->warnings, 'Deveria emitir aviso sobre times sem goleiro fixo.');
        $this->assertStringContainsString('sem goleiro fixo', implode(' ', $result->warnings));
    }

    #[Test]
    public function it_moves_excess_goalkeepers_to_line_using_secondary_position(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [
            ...$this->makeLinePool(8),
            new PlayerInput(101, 6, Position::GOL, Position::ZAG, true),
            new PlayerInput(102, 7, Position::GOL, Position::ATA, true),
            new PlayerInput(103, 8, Position::GOL, Position::MEI, true),
        ];

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 5,
            hasFixedGoalkeepers: true,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertCount(2, $result->teams);

        $goalkeepers = 0;
        $excessAsLine = 0;
        foreach ($result->teams as $team) {
            foreach ($team->players as $p) {
                if ($p->isGoalkeeper) {
                    $goalkeepers++;
                    continue;
                }
                if (in_array($p->userId, [101, 102, 103], true)) {
                    $excessAsLine++;
                }
            }
        }

        $this->assertSame(2, $goalkeepers, 'Exatamente N=2 goleiros fixos ocupam a posição de goleiro.');
        $this->assertSame(1, $excessAsLine, 'Goleiro excedente vira jogador de linha.');
    }

    #[Test]
    public function it_warns_when_excess_goalkeeper_has_no_secondary_position(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [
            ...$this->makeLinePool(8),
            new PlayerInput(101, 8, Position::GOL, Position::ZAG, true),
            new PlayerInput(102, 7, Position::GOL, Position::ATA, true),
            new PlayerInput(103, 6, Position::GOL, null, true),
        ];

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 5,
            hasFixedGoalkeepers: true,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertTrue(
            $this->warningsContain($result, 'sem 2ª posição'),
            'Deveria emitir aviso para goleiro excedente sem 2ª posição.',
        );

        $this->assertFalse(
            $this->userIsAssignedAnywhere($result, 103),
            'O goleiro excedente sem 2ª posição deve ficar de fora da escalação.',
        );
    }

    #[Test]
    public function it_is_deterministic_for_same_input(): void
    {
        $strategy = new SnakeDraftStrategy();

        $input = new DrawInput(
            players: $this->makeLinePool(12),
            playersPerTeam: 6,
            hasFixedGoalkeepers: false,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        );

        $first = $strategy->draw($input);
        $second = $strategy->draw($input);

        $this->assertSame(
            $this->fingerprint($first),
            $this->fingerprint($second),
            'Mesmo input deve produzir mesmo output.',
        );
    }

    #[Test]
    public function it_balances_high_and_low_skill_players_across_teams(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [
            new PlayerInput(1, 10, Position::ATA, null, false),
            new PlayerInput(2, 10, Position::MEI, null, false),
            new PlayerInput(3, 3, Position::ZAG, null, false),
            new PlayerInput(4, 3, Position::LAT, null, false),
            new PlayerInput(5, 8, Position::ATA, null, false),
            new PlayerInput(6, 8, Position::MEI, null, false),
            new PlayerInput(7, 5, Position::ZAG, null, false),
            new PlayerInput(8, 5, Position::LAT, null, false),
        ];

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 4,
            hasFixedGoalkeepers: false,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertCount(2, $result->teams);

        $sums = array_map(
            static fn (TeamComposition $t): int => $t->totalRating(),
            $result->teams,
        );

        $this->assertLessThanOrEqual(
            2,
            max($sums) - min($sums),
            'Snake draft deve alternar picks de alta e baixa skill.',
        );
    }

    #[Test]
    public function it_refuses_to_form_teams_with_fewer_than_two_line_players(): void
    {
        $strategy = new SnakeDraftStrategy();

        $result = $strategy->draw(new DrawInput(
            players: [new PlayerInput(1, 5, Position::MEI, null, false)],
            playersPerTeam: 5,
            hasFixedGoalkeepers: false,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertSame([], $result->teams);
        $this->assertNotEmpty($result->warnings);
    }

    #[Test]
    public function it_warns_when_position_ratio_cannot_be_fixed_by_pool(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [];
        for ($i = 1; $i <= 6; $i++) {
            $players[] = new PlayerInput($i, 11 - $i, Position::ZAG, null, false);
        }
        $players[] = new PlayerInput(7, 4, Position::ATA, null, false);
        $players[] = new PlayerInput(8, 3, Position::ATA, null, false);

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 4,
            hasFixedGoalkeepers: false,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $this->assertCount(2, $result->teams);
        $this->assertTrue(
            $this->warningsContain($result, 'excesso')
            || $this->warningsContain($result, 'swap invi'),
            'Deveria emitir aviso quando o pool força violação de posição.',
        );
    }

    #[Test]
    public function it_uses_secondary_position_for_line_players_with_primary_gol(): void
    {
        $strategy = new SnakeDraftStrategy();

        $players = [
            new PlayerInput(1, 8, Position::GOL, Position::ATA, false),
            new PlayerInput(2, 7, Position::MEI, null, false),
            new PlayerInput(3, 6, Position::ZAG, null, false),
            new PlayerInput(4, 5, Position::LAT, null, false),
        ];

        $result = $strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: 2,
            hasFixedGoalkeepers: false,
            starTolerance: 2,
            positionMaxRatio: 0.6,
        ));

        $found = false;
        foreach ($result->teams as $team) {
            foreach ($team->players as $p) {
                if ($p->userId === 1) {
                    $this->assertSame(Position::ATA, $p->assignedPosition);
                    $found = true;
                }
            }
        }
        $this->assertTrue($found);
    }

    /**
     * @return list<PlayerInput>
     */
    private function makeLinePool(int $count): array
    {
        $positions = [Position::ZAG, Position::LAT, Position::MEI, Position::ATA];
        $players = [];
        for ($i = 1; $i <= $count; $i++) {
            $players[] = new PlayerInput(
                userId: $i,
                skill: (($i - 1) % 10) + 1,
                primaryPosition: $positions[($i - 1) % 4],
                secondaryPosition: null,
                isFixedGoalkeeper: false,
            );
        }

        return $players;
    }

    private function assertNoDuplicateUsers(DrawResult $result): void
    {
        $seen = [];
        foreach ($result->teams as $team) {
            foreach ($team->players as $player) {
                $this->assertNotContains(
                    $player->userId,
                    $seen,
                    sprintf('Usuário %d aparece em mais de um time.', $player->userId),
                );
                $seen[] = $player->userId;
            }
        }
    }

    private function assertGoalkeepersNotCountedInTotalRating(DrawResult $result): void
    {
        foreach ($result->teams as $team) {
            $expected = 0;
            foreach ($team->players as $p) {
                if (! $p->isGoalkeeper) {
                    $expected += $p->skill;
                }
            }
            $this->assertSame($expected, $team->totalRating());
        }
    }

    private function warningsContain(DrawResult $result, string $needle): bool
    {
        foreach ($result->warnings as $w) {
            if (str_contains($w, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function userIsAssignedAnywhere(DrawResult $result, int $userId): bool
    {
        foreach ($result->teams as $team) {
            foreach ($team->players as $p) {
                if ($p->userId === $userId) {
                    return true;
                }
            }
        }

        return false;
    }

    private function fingerprint(DrawResult $result): string
    {
        $data = [];
        foreach ($result->teams as $team) {
            $rowTeam = ['name' => $team->name, 'players' => []];
            foreach ($team->players as $p) {
                $rowTeam['players'][] = [
                    'user' => $p->userId,
                    'skill' => $p->skill,
                    'pos' => $p->assignedPosition?->value,
                    'gk' => $p->isGoalkeeper,
                ];
            }
            $data['teams'][] = $rowTeam;
        }
        $data['warnings'] = $result->warnings;

        return (string) json_encode($data, JSON_THROW_ON_ERROR);
    }
}
