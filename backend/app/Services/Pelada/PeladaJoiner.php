<?php

declare(strict_types=1);

namespace App\Services\Pelada;

use App\Domain\Enums\MemberRole;
use App\Models\Pelada;
use App\Models\PeladaInvite;
use App\Models\PeladaMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PeladaJoiner
{
    public function joinByShortCode(User $user, string $shortCode): PeladaMember
    {
        $pelada = Pelada::where('short_code', $shortCode)->first();

        if (! $pelada) {
            throw new RuntimeException('Pelada não encontrada.');
        }

        return $this->attach($user, $pelada);
    }

    public function joinByInviteToken(User $user, string $token): PeladaMember
    {
        return DB::transaction(function () use ($user, $token): PeladaMember {
            $invite = PeladaInvite::where('token', $token)->lockForUpdate()->first();

            if (! $invite || ! $invite->isValid()) {
                throw new RuntimeException('Convite inválido ou expirado.');
            }

            $member = $this->attach($user, $invite->pelada);

            $invite->increment('used_count');

            return $member;
        });
    }

    public function createInvite(User $admin, Pelada $pelada): PeladaInvite
    {
        return PeladaInvite::create([
            'pelada_id' => $pelada->id,
            'created_by' => $admin->id,
            'token' => bin2hex(random_bytes(24)),
            'expires_at' => now()->addHours((int) config('cade-os-times.invite.ttl_hours')),
            'use_limit' => null,
        ]);
    }

    private function attach(User $user, Pelada $pelada): PeladaMember
    {
        $existing = PeladaMember::where('pelada_id', $pelada->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return PeladaMember::create([
            'pelada_id' => $pelada->id,
            'user_id' => $user->id,
            'role' => MemberRole::MEMBER->value,
            'is_fixed_goalkeeper' => false,
            'joined_at' => now(),
        ]);
    }
}
