<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeladaInvite extends Model
{
    protected $fillable = [
        'pelada_id',
        'created_by',
        'token',
        'expires_at',
        'use_limit',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'use_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function pelada(): BelongsTo
    {
        return $this->belongsTo(Pelada::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        if ($this->expires_at->isPast()) {
            return false;
        }

        return $this->use_limit === null || $this->used_count < $this->use_limit;
    }
}
