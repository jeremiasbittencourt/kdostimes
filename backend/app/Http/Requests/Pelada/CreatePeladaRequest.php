<?php

declare(strict_types=1);

namespace App\Http\Requests\Pelada;

use Illuminate\Foundation\Http\FormRequest;

class CreatePeladaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'location' => ['nullable', 'string', 'max:180'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'time' => ['required', 'date_format:H:i'],
            'players_per_team' => ['required', 'integer', 'between:3,11'],
            'has_fixed_goalkeepers' => ['required', 'boolean'],
            'confirmation_deadline_hours' => ['required', 'integer', 'between:1,24'],
            'enabled_voting_category_codes' => ['nullable', 'array'],
            'enabled_voting_category_codes.*' => ['string', 'exists:voting_categories,code'],
        ];
    }
}
