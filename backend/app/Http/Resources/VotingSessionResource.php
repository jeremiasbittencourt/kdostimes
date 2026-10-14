<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VotingSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pelada_session_id' => $this->pelada_session_id,
            'opens_at' => $this->opens_at->toIso8601String(),
            'closes_at' => $this->closes_at->toIso8601String(),
            'status' => $this->status->value,
            'categories' => VotingCategoryResource::collection($this->whenLoaded('categories')),
            'my_votes' => $this->when(
                $this->getAttribute('my_votes') !== null,
                fn () => $this->getAttribute('my_votes'),
            ),
        ];
    }
}
