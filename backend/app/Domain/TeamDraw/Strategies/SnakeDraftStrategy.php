<?php

declare(strict_types=1);

namespace App\Domain\TeamDraw\Strategies;

use App\Domain\Enums\Position;
use App\Domain\TeamDraw\DTO\AssignedPlayer;
use App\Domain\TeamDraw\DTO\DrawInput;
use App\Domain\TeamDraw\DTO\DrawResult;
use App\Domain\TeamDraw\DTO\PlayerInput;
use App\Domain\TeamDraw\DTO\TeamComposition;
use App\Domain\TeamDraw\TeamDrawStrategyInterface;

/**
 * Snake draft com balanceamento por estrelas e ajuste de posições.
 *
 * Segue o pseudocódigo da seção 4 do brief:
 *  1. Separa goleiros fixos, distribui 1 por time.
 *  2. Ordena jogadores de linha por skill (desc).
 *  3. Snake draft: t1..tN, tN..t1, t1..tN, ...
 *  4. Se algum time ultrapassar position_max_ratio em uma posição, tenta swaps
 *     preservando a soma de estrelas dentro de star_tolerance.
 */
final class SnakeDraftStrategy implements TeamDrawStrategyInterface
{
    public function draw(DrawInput $input): DrawResult
    {
        $warnings = [];

        [$goalkeepers, $linePlayers] = $this->partition($input);

        $teamCount = $this->calculateTeamCount($linePlayers, $input->playersPerTeam);
        if ($teamCount < 2) {
            return new DrawResult([], ['Pool insuficiente de jogadores de linha para formar dois times.']);
        }

        $teamGoalkeepers = $this->assignGoalkeepers($goalkeepers, $teamCount, $input, $linePlayers, $warnings);

        $teamLineSlots = $this->snakeDraft($linePlayers, $teamCount);

        $this->rebalancePositions($teamLineSlots, $input, $warnings);

        $teams = $this->buildTeams($teamLineSlots, $teamGoalkeepers);

        $this->checkStarTolerance($teams, $input->starTolerance, $warnings);

        return new DrawResult($teams, $warnings);
    }

    /**
     * @return array{0: list<PlayerInput>, 1: list<PlayerInput>}
     */
    private function partition(DrawInput $input): array
    {
        $goalkeepers = [];
        $linePlayers = [];

        foreach ($input->players as $player) {
            if ($input->hasFixedGoalkeepers && $player->isFixedGoalkeeper) {
                $goalkeepers[] = $player;
            } else {
                $linePlayers[] = $player;
            }
        }

        return [$goalkeepers, $linePlayers];
    }

    /**
     * @param  list<PlayerInput>  $linePlayers
     */
    private function calculateTeamCount(array $linePlayers, int $playersPerTeam): int
    {
        $count = count($linePlayers);
        if ($count === 0) {
            return 0;
        }

        return (int) max(2, ceil($count / $playersPerTeam));
    }

    /**
     * @param  list<PlayerInput>  $goalkeepers
     * @param  list<PlayerInput>  $linePlayers  (modificado por referência quando há goleiros excedentes)
     * @param  list<string>  $warnings          (modificado por referência)
     * @return array<int, ?AssignedPlayer>
     */
    private function assignGoalkeepers(
        array $goalkeepers,
        int $teamCount,
        DrawInput $input,
        array &$linePlayers,
        array &$warnings,
    ): array {
        $teamGoalkeepers = array_fill(0, $teamCount, null);

        if (! $input->hasFixedGoalkeepers || $goalkeepers === []) {
            if ($input->hasFixedGoalkeepers && $goalkeepers === []) {
                $warnings[] = 'Pelada com goleiros fixos, mas nenhum confirmado para esta sessão — sugerimos rodízio.';
            }

            return $teamGoalkeepers;
        }

        usort(
            $goalkeepers,
            fn (PlayerInput $a, PlayerInput $b): int => [$b->skill, $a->userId] <=> [$a->skill, $b->userId],
        );

        $toDistribute = min(count($goalkeepers), $teamCount);
        for ($i = 0; $i < $toDistribute; $i++) {
            $gk = $goalkeepers[$i];
            $teamGoalkeepers[$i] = new AssignedPlayer(
                userId: $gk->userId,
                skill: $gk->skill,
                assignedPosition: Position::GOL,
                isGoalkeeper: true,
            );
        }

        if (count($goalkeepers) > $teamCount) {
            for ($i = $teamCount; $i < count($goalkeepers); $i++) {
                $gk = $goalkeepers[$i];
                if ($gk->secondaryPosition !== null) {
                    $linePlayers[] = new PlayerInput(
                        userId: $gk->userId,
                        skill: $gk->skill,
                        primaryPosition: $gk->secondaryPosition,
                        secondaryPosition: null,
                        isFixedGoalkeeper: false,
                    );
                } else {
                    $warnings[] = sprintf(
                        'Goleiro fixo (id=%d) sem 2ª posição — ficou de fora da escalação.',
                        $gk->userId,
                    );
                }
            }
        }

        if (count($goalkeepers) < $teamCount) {
            $warnings[] = sprintf(
                '%d time(s) sem goleiro fixo — considere rodízio durante a partida.',
                $teamCount - count($goalkeepers),
            );
        }

        return $teamGoalkeepers;
    }

    /**
     * @param  list<PlayerInput>  $linePlayers
     * @return array<int, list<AssignedPlayer>>
     */
    private function snakeDraft(array $linePlayers, int $teamCount): array
    {
        usort(
            $linePlayers,
            fn (PlayerInput $a, PlayerInput $b): int => [$b->skill, $a->userId] <=> [$a->skill, $b->userId],
        );

        $slots = array_fill(0, $teamCount, []);

        foreach ($linePlayers as $i => $player) {
            $round = intdiv($i, $teamCount);
            $positionInRound = $i % $teamCount;
            $teamIndex = $round % 2 === 0
                ? $positionInRound
                : ($teamCount - 1) - $positionInRound;

            $slots[$teamIndex][] = new AssignedPlayer(
                userId: $player->userId,
                skill: $player->skill,
                assignedPosition: $player->effectiveLinePosition(),
                isGoalkeeper: false,
            );
        }

        return $slots;
    }

    /**
     * @param  array<int, list<AssignedPlayer>>  $teamLineSlots
     * @param  list<string>  $warnings
     */
    private function rebalancePositions(array &$teamLineSlots, DrawInput $input, array &$warnings): void
    {
        $maxPasses = count($teamLineSlots) * 4;

        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $violation = $this->findWorstPositionViolation($teamLineSlots, $input->positionMaxRatio);
            if ($violation === null) {
                return;
            }

            [$teamIdx, $position] = $violation;

            if (! $this->trySwapToFix(
                $teamLineSlots,
                $teamIdx,
                $position,
                $input->starTolerance,
                $input->positionMaxRatio,
            )) {
                $warnings[] = sprintf(
                    'Time %d com excesso de %s — swap inviável dentro da tolerância.',
                    $teamIdx + 1,
                    $position,
                );

                return;
            }
        }
    }

    /**
     * @param  array<int, list<AssignedPlayer>>  $teamLineSlots
     * @return array{0: int, 1: string}|null
     */
    private function findWorstPositionViolation(array $teamLineSlots, float $maxRatio): ?array
    {
        $worst = null;
        $worstRatio = $maxRatio;

        foreach ($teamLineSlots as $teamIdx => $players) {
            if ($players === []) {
                continue;
            }

            $counts = [];
            foreach ($players as $player) {
                $key = $player->assignedPosition?->value;
                if ($key === null) {
                    continue;
                }
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }

            $size = count($players);
            foreach ($counts as $pos => $count) {
                $ratio = $count / $size;
                if ($ratio > $worstRatio) {
                    $worst = [$teamIdx, $pos];
                    $worstRatio = $ratio;
                }
            }
        }

        return $worst;
    }

    /**
     * @param  array<int, list<AssignedPlayer>>  $teamLineSlots
     */
    private function trySwapToFix(
        array &$teamLineSlots,
        int $fromTeam,
        string $position,
        int $starTolerance,
        float $positionMaxRatio,
    ): bool {
        $giveIdx = $this->findPlayerIndexByPosition($teamLineSlots[$fromTeam], $position);
        if ($giveIdx === null) {
            return false;
        }

        $givePlayer = $teamLineSlots[$fromTeam][$giveIdx];

        foreach ($teamLineSlots as $otherTeam => $otherPlayers) {
            if ($otherTeam === $fromTeam) {
                continue;
            }

            foreach ($otherPlayers as $receiveIdx => $candidate) {
                if (($candidate->assignedPosition?->value) === $position) {
                    continue;
                }

                $simulated = $teamLineSlots;
                $simulated[$fromTeam][$giveIdx] = $candidate;
                $simulated[$otherTeam][$receiveIdx] = $givePlayer;

                if ($this->maxMinStarDelta($simulated) <= $starTolerance
                    && $this->findWorstPositionViolation($simulated, $positionMaxRatio) === null) {
                    $teamLineSlots = $simulated;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<AssignedPlayer>  $players
     */
    private function findPlayerIndexByPosition(array $players, string $position): ?int
    {
        foreach ($players as $idx => $player) {
            if (($player->assignedPosition?->value) === $position) {
                return $idx;
            }
        }

        return null;
    }

    /**
     * @param  array<int, list<AssignedPlayer>>  $teamLineSlots
     */
    private function maxMinStarDelta(array $teamLineSlots): int
    {
        $sums = array_map(
            static fn (array $players): int => array_sum(array_map(
                static fn (AssignedPlayer $p): int => $p->skill,
                $players,
            )),
            $teamLineSlots,
        );

        return (int) (max($sums) - min($sums));
    }

    /**
     * @param  array<int, list<AssignedPlayer>>  $teamLineSlots
     * @param  array<int, ?AssignedPlayer>  $teamGoalkeepers
     * @return list<TeamComposition>
     */
    private function buildTeams(array $teamLineSlots, array $teamGoalkeepers): array
    {
        $teams = [];
        $teamCount = count($teamLineSlots);

        for ($i = 0; $i < $teamCount; $i++) {
            $players = [];
            if (($teamGoalkeepers[$i] ?? null) !== null) {
                $players[] = $teamGoalkeepers[$i];
            }
            foreach ($teamLineSlots[$i] as $player) {
                $players[] = $player;
            }

            $teams[] = new TeamComposition(
                name: 'Time ' . chr(ord('A') + $i),
                players: $players,
            );
        }

        return $teams;
    }

    /**
     * @param  list<TeamComposition>  $teams
     * @param  list<string>  $warnings
     */
    private function checkStarTolerance(array $teams, int $tolerance, array &$warnings): void
    {
        if ($teams === []) {
            return;
        }

        $ratings = array_map(
            static fn (TeamComposition $team): int => $team->totalRating(),
            $teams,
        );

        $delta = max($ratings) - min($ratings);
        if ($delta > $tolerance) {
            $warnings[] = sprintf(
                'Diferença de estrelas entre times foi de %d (tolerância: %d). Considere ajustar manualmente.',
                $delta,
                $tolerance,
            );
        }
    }
}
