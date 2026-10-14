<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeladaInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'expires_at' => $this->expires_at->toIso8601String(),
            'use_limit' => $this->use_limit,
            'used_count' => $this->used_count,
        ];
    }
}
