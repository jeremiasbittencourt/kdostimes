<?php

declare(strict_types=1);

namespace App\Services\Draw;

use App\Domain\Enums\PresenceStatus;
use App\Domain\TeamDraw\DTO\DrawInput;
use App\Domain\TeamDraw\DTO\PlayerInput;
use App\Domain\TeamDraw\TeamDrawStrategyInterface;
use App\Models\Draw;
use App\Models\PeladaMember;
use App\Models\PeladaSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TeamDrawService
{
    public function __construct(
        private readonly TeamDrawStrategyInterface $strategy,
    ) {
    }

    public function drawFor(PeladaSession $session, User $creator): Draw
    {
        $presences = $session->presences()
            ->where('status', PresenceStatus::CONFIRMED->value)
            ->with(['user.positions'])
            ->get();

        if ($presences->count() < 2) {
            throw new RuntimeException('Confirme pelo menos 2 jogadores antes de sortear.');
        }

        $pelada = $session->pelada()->firstOrFail();

        $fixedGoalkeeperMap = PeladaMember::query()
            ->where('pelada_id', $pelada->id)
            ->whereIn('user_id', $presences->pluck('user_id')->all())
            ->pluck('is_fixed_goalkeeper', 'user_id');

        $players = [];
        foreach ($presences as $presence) {
            $user = $presence->user;
            $positions = $user->positions;
            $players[] = new PlayerInput(
                userId: $user->id,
                skill: (int) $user->skill_rating,
                primaryPosition: $positions->firstWhere('priority', 1)?->position,
                secondaryPosition: $positions->firstWhere('priority', 2)?->position,
                isFixedGoalkeeper: $pelada->has_fixed_goalkeepers
                    && (bool) ($fixedGoalkeeperMap[$user->id] ?? false),
            );
        }

        $result = $this->strategy->draw(new DrawInput(
            players: $players,
            playersPerTeam: (int) $pelada->players_per_team,
            hasFixedGoalkeepers: (bool) $pelada->has_fixed_goalkeepers,
            starTolerance: (int) config('cade-os-times.draw.star_tolerance'),
            positionMaxRatio: (float) config('cade-os-times.draw.position_max_ratio'),
        ));

        if ($result->teams === []) {
            throw new RuntimeException('Pool insuficiente de jogadores de linha para formar times.');
        }

        return DB::transaction(function () use ($session, $creator, $result): Draw {
            Draw::where('pelada_session_id', $session->id)
                ->where('published', false)
                ->delete();

            $draw = Draw::create([
                'pelada_session_id' => $session->id,
                'created_by' => $creator->id,
                'published' => false,
            ]);

            foreach ($result->teams as $team) {
                $drawTeam = $draw->teams()->create([
                    'name' => $team->name,
                    'total_rating' => $team->totalRating(),
                ]);

                foreach ($team->players as $player) {
                    $drawTeam->players()->create([
                        'user_id' => $player->userId,
                        'assigned_position' => $player->assignedPosition?->value,
                        'is_goalkeeper' => $player->isGoalkeeper,
                    ]);
                }
            }

            $draw->setAttribute('warnings', $result->warnings);

            return $draw;
        });
    }
}
