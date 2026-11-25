<?php

namespace App\Http\Requests\Plan;

use App\Enums\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // pembatasan admin dilakukan oleh middleware 'admin'
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'discount_percent' => ['sometimes', 'integer', 'between:0,100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
