<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\MemberRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PeladaMember extends Pivot
{
    protected $table = 'pelada_members';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'pelada_id',
        'user_id',
        'role',
        'is_fixed_goalkeeper',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'is_fixed_goalkeeper' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function pelada(): BelongsTo
    {
        return $this->belongsTo(Pelada::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
