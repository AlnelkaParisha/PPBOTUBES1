<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:movie,series,music'],
            'description' => ['nullable', 'string'],
            'plan_ids' => ['sometimes', 'array', 'min:1'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
        ];
    }
}
