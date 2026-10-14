<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\PresenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceConfirmation extends Model
{
    protected $fillable = [
        'pelada_session_id',
        'user_id',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PresenceStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PeladaSession::class, 'pelada_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
