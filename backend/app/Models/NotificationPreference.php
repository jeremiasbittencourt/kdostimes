<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Enums\NotificationEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'event_type', 'enabled'];

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEvent::class,
            'enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
