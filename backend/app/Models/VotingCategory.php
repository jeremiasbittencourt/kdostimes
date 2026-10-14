<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\VoteCategoryType;
use Illuminate\Database\Eloquent\Model;

class VotingCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'points_per_vote',
        'bonus_for_winner',
    ];

    protected function casts(): array
    {
        return [
            'type' => VoteCategoryType::class,
            'points_per_vote' => 'integer',
            'bonus_for_winner' => 'integer',
        ];
    }
}
