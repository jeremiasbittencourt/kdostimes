<?php

declare(strict_types=1);

namespace App\Http\Requests\Voting;

use Illuminate\Foundation\Http\FormRequest;

class CastVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:voting_categories,id'],
            'voted_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
