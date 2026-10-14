<?php

declare(strict_types=1);

namespace App\Http\Requests\Pelada;

use App\Domain\Enums\MemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'is_fixed_goalkeeper' => ['sometimes', 'boolean'],
            'role' => ['sometimes', Rule::enum(MemberRole::class)],
        ];
    }
}
