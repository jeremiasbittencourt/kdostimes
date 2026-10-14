<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PeladaSession extends Model
{
    protected $fillable = [
        'pelada_id',
        'scheduled_at',
        'deadline_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'deadline_at' => 'datetime',
            'status' => SessionStatus::class,
        ];
    }

    public function pelada(): BelongsTo
    {
        return $this->belongsTo(Pelada::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(PresenceConfirmation::class);
    }

    public function draws(): HasMany
    {
        return $this->hasMany(Draw::class);
    }

    public function publishedDraw(): HasOne
    {
        return $this->hasOne(Draw::class)->where('published', true);
    }

    public function votingSession(): HasOne
    {
        return $this->hasOne(VotingSession::class);
    }
}
