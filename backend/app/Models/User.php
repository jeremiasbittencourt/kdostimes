<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'skill_rating',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skill_rating' => 'integer',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(PlayerPosition::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function peladas(): BelongsToMany
    {
        return $this->belongsToMany(Pelada::class, 'pelada_members')
            ->using(PeladaMember::class)
            ->withPivot(['role', 'is_fixed_goalkeeper', 'joined_at'])
            ->withTimestamps();
    }

    public function administeredPeladas(): HasMany
    {
        return $this->hasMany(Pelada::class, 'admin_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PeladaMember::class);
    }
}
