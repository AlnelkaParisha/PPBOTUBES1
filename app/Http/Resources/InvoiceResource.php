<?php

namespace App\Http\Resources;

use App\Billing\BillingService;
use App\Enums\BillingCycle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscription = $this->subscription;
        $plan = $subscription?->plan;
        $isFamily = $plan !== null && $plan->billing_cycle === BillingCycle::Family;

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'credit_applied' => $this->credit_applied,
            'total' => $this->total,
            'status' => $this->status->value,
            'period_start' => $this->period_start->toDateString(),
            'period_end' => $this->period_end->toDateString(),
            'due_date' => $this->due_date->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            // Riwayat percobaan bayar (gagal dan berhasil)
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            // Khusus paket family: porsi tiap anggota, urutannya sama dengan daftar anggota
            'member_shares' => $this->when($isFamily && $subscription->relationLoaded('members'), function () use ($plan, $subscription) {
                $shares = app(BillingService::class)->splitAmount($plan, $this->total, $subscription->members_count);

                return $subscription->members->values()->map(fn ($member, $i) => [
                    'name' => $member->name,
                    'email' => $member->email,
                    'share' => $shares[$i] ?? 0,
                ])->all();
            }),
        ];
    }
}
