<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeladaSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pelada_id' => $this->pelada_id,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'deadline_at' => $this->deadline_at->toIso8601String(),
            'status' => $this->status->value,
            'presences' => PresenceConfirmationResource::collection($this->whenLoaded('presences')),
        ];
    }
}
