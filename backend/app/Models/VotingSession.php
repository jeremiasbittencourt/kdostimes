<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\VotingSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingSession extends Model
{
    protected $fillable = [
        'pelada_session_id',
        'opens_at',
        'closes_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'status' => VotingSessionStatus::class,
        ];
    }

    public function peladaSession(): BelongsTo
    {
        return $this->belongsTo(PeladaSession::class, 'pelada_session_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
