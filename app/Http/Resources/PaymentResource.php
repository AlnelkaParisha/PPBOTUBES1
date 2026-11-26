<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_method' => $this->payment_method->value,
            'amount' => $this->amount,
            'status' => $this->status->value,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
