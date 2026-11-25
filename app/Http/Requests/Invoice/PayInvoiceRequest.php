<?php

namespace App\Http\Requests\Invoice;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(PaymentMethod::selectable())],
            // Khusus uji coba: simulate=failed membuat pembayaran gagal. Diabaikan di production.
            'simulate' => ['sometimes', 'in:success,failed'],
        ];
    }
}
