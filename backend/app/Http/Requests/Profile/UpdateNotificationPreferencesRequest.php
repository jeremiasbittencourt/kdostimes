<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Domain\Enums\NotificationEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.event_type' => ['required', Rule::enum(NotificationEvent::class)],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }
}
