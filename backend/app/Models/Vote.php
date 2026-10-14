<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'voting_session_id',
        'voting_category_id',
        'voter_user_id',
        'voted_user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VotingSession::class, 'voting_session_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VotingCategory::class, 'voting_category_id');
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_user_id');
    }

    public function voted(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voted_user_id');
    }
}
