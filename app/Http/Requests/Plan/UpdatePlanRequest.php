<?php

namespace App\Http\Requests\Plan;

use App\Enums\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'billing_cycle' => ['sometimes', Rule::enum(BillingCycle::class)],
            'discount_percent' => ['sometimes', 'integer', 'between:0,100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
