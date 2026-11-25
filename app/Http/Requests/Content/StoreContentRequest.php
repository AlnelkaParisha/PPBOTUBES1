<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // pembatasan admin dilakukan oleh middleware 'admin'
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:movie,series,music'],
            'description' => ['nullable', 'string'],
            'plan_ids' => ['required', 'array', 'min:1'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
        ];
    }
}
