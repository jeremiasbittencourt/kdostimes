<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrawTeamPlayer extends Model
{
    protected $fillable = [
        'draw_team_id',
        'user_id',
        'assigned_position',
        'is_goalkeeper',
    ];

    protected function casts(): array
    {
        return [
            'assigned_position' => Position::class,
            'is_goalkeeper' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(DrawTeam::class, 'draw_team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
