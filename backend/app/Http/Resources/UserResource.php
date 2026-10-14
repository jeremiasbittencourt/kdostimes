<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'skill_rating' => $this->skill_rating,
            'avatar_url' => $this->avatar_url,
            'positions' => PlayerPositionResource::collection($this->whenLoaded('positions')),
            'notification_preferences' => NotificationPreferenceResource::collection(
                $this->whenLoaded('notificationPreferences')
            ),
        ];
    }
}
