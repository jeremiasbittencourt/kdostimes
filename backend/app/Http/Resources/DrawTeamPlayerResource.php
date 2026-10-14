<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrawTeamPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'assigned_position' => $this->assigned_position?->value,
            'is_goalkeeper' => $this->is_goalkeeper,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
