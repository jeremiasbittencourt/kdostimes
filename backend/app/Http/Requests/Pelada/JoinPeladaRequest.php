<?php

declare(strict_types=1);

namespace App\Http\Requests\Pelada;

use Illuminate\Foundation\Http\FormRequest;

class JoinPeladaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'short_code' => ['required_without:invite_token', 'nullable', 'string', 'max:16'],
            'invite_token' => ['required_without:short_code', 'nullable', 'string', 'max:80'],
        ];
    }
}
