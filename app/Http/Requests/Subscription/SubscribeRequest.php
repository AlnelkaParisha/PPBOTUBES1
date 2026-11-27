<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            // Hanya dipakai paket family. Pemilik akun ikut ditulis sebagai salah satu anggota.
            'members' => ['sometimes', 'array', 'min:1', 'max:6'],
            'members.*.name' => ['required', 'string', 'max:100'],
            'members.*.email' => ['required', 'email', 'max:255', 'distinct:ignore_case'],
        ];
    }
}
