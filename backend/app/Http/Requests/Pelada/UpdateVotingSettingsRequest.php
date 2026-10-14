<?php

declare(strict_types=1);

namespace App\Http\Requests\Pelada;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVotingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.code' => ['required', 'string', 'exists:voting_categories,code'],
            'settings.*.enabled' => ['required', 'boolean'],
        ];
    }
}
