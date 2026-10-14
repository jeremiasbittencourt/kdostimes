<?php

declare(strict_types=1);

namespace App\Services\Pelada;

use App\Domain\Enums\MemberRole;
use App\Models\Pelada;
use App\Models\PeladaVotingSetting;
use App\Models\User;
use App\Models\VotingCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeladaCreator
{
    public function create(User $admin, array $data): Pelada
    {
        return DB::transaction(function () use ($admin, $data): Pelada {
            $pelada = Pelada::create([
                'short_code' => $this->generateShortCode(),
                'admin_id' => $admin->id,
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
                'day_of_week' => $data['day_of_week'],
                'time' => $data['time'],
                'players_per_team' => $data['players_per_team'],
                'has_fixed_goalkeepers' => $data['has_fixed_goalkeepers'] ?? false,
                'confirmation_deadline_hours' => $data['confirmation_deadline_hours'] ?? 2,
            ]);

            $pelada->members()->create([
                'user_id' => $admin->id,
                'role' => MemberRole::ADMIN->value,
                'is_fixed_goalkeeper' => false,
                'joined_at' => now(),
            ]);

            $enabledCodes = $data['enabled_voting_category_codes'] ?? null;
            $this->bootstrapVotingSettings($pelada, $enabledCodes);

            return $pelada;
        });
    }

    private function bootstrapVotingSettings(Pelada $pelada, ?array $enabledCodes): void
    {
        $rows = VotingCategory::all()->map(function (VotingCategory $category) use ($enabledCodes, $pelada) {
            return [
                'pelada_id' => $pelada->id,
                'voting_category_id' => $category->id,
                'enabled' => $enabledCodes === null ? true : in_array($category->code, $enabledCodes, true),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->all();

        PeladaVotingSetting::insert($rows);
    }

    private function generateShortCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $candidate = 'PEL-' . $suffix;
        } while (Pelada::where('short_code', $candidate)->exists());

        return $candidate;
    }
}
