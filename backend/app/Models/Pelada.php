<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelada extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_code',
        'admin_id',
        'name',
        'location',
        'day_of_week',
        'time',
        'players_per_team',
        'has_fixed_goalkeepers',
        'confirmation_deadline_hours',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'time' => 'string',
            'players_per_team' => 'integer',
            'has_fixed_goalkeepers' => 'boolean',
            'confirmation_deadline_hours' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PeladaMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pelada_members')
            ->using(PeladaMember::class)
            ->withPivot(['role', 'is_fixed_goalkeeper', 'joined_at'])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PeladaSession::class);
    }

    public function votingSettings(): HasMany
    {
        return $this->hasMany(PeladaVotingSetting::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(PeladaInvite::class);
    }

    public function rankingEntries(): HasMany
    {
        return $this->hasMany(RankingEntry::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isAdmin(User $user): bool
    {
        return $this->admin_id === $user->id
            || $this->members()
                ->where('user_id', $user->id)
                ->where('role', MemberRole::ADMIN->value)
                ->exists();
    }
}
