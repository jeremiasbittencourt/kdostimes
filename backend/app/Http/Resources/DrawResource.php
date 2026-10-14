<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pelada_session_id' => $this->pelada_session_id,
            'published' => $this->published,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'teams' => DrawTeamResource::collection($this->whenLoaded('teams')),
            'warnings' => $this->when(
                $this->getAttribute('warnings') !== null,
                fn () => $this->getAttribute('warnings'),
            ),
        ];
    }
}
