<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Domain\Enums\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'skill_rating' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:500'],

            'positions' => ['sometimes', 'array', 'max:2'],
            'positions.*.position' => [
                'required_with:positions',
                Rule::enum(Position::class),
            ],
            'positions.*.priority' => ['required_with:positions', 'integer', 'in:1,2'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $positions = $this->input('positions');
            if (! is_array($positions) || $positions === []) {
                return;
            }

            $priorities = array_column($positions, 'priority');
            if (count($priorities) !== count(array_unique($priorities))) {
                $validator->errors()->add('positions', 'Prioridades de posição não podem repetir.');
            }

            $codes = array_column($positions, 'position');
            if (count($codes) !== count(array_unique($codes))) {
                $validator->errors()->add('positions', 'A mesma posição não pode ser listada duas vezes.');
            }
        });
    }
}
