<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingEntry extends Model
{
    protected $fillable = [
        'pelada_id',
        'user_id',
        'voting_category_id',
        'total_points',
        'wins_count',
        'last_computed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'integer',
            'wins_count' => 'integer',
            'last_computed_at' => 'datetime',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(VotingCategory::class, 'voting_category_id');
    }
}
