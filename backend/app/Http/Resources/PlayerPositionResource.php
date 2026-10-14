<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerPositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'position' => $this->position->value,
            'label' => $this->position->label(),
            'priority' => $this->priority,
        ];
    }
}
