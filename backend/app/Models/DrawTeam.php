<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrawTeam extends Model
{
    protected $fillable = ['draw_id', 'name', 'total_rating'];

    protected function casts(): array
    {
        return ['total_rating' => 'integer'];
    }

    public function draw(): BelongsTo
    {
        return $this->belongsTo(Draw::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(DrawTeamPlayer::class);
    }
}
