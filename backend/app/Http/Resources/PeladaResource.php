<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeladaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'short_code' => $this->short_code,
            'name' => $this->name,
            'location' => $this->location,
            'day_of_week' => $this->day_of_week,
            'time' => $this->time,
            'players_per_team' => $this->players_per_team,
            'has_fixed_goalkeepers' => $this->has_fixed_goalkeepers,
            'confirmation_deadline_hours' => $this->confirmation_deadline_hours,
            'admin_id' => $this->admin_id,
            'admin' => new UserResource($this->whenLoaded('admin')),
            'members' => PeladaMemberResource::collection($this->whenLoaded('members')),
        ];
    }
}
