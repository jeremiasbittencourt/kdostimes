<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerPosition extends Model
{
    protected $fillable = ['user_id', 'position', 'priority'];

    protected function casts(): array
    {
        return [
            'position' => Position::class,
            'priority' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
