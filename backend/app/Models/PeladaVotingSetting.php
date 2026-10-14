<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeladaVotingSetting extends Model
{
    protected $fillable = ['pelada_id', 'voting_category_id', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function pelada(): BelongsTo
    {
        return $this->belongsTo(Pelada::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VotingCategory::class, 'voting_category_id');
    }
}
