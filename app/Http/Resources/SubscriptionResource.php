<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'previous_plan' => new PlanResource($this->whenLoaded('previousPlan')),
            'period_start' => $this->period_start->toDateString(),
            'period_end' => $this->period_end->toDateString(),
            'members_count' => $this->members_count,
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
            ])->all()),
            'credit_balance' => $this->credit_balance,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
        ];
    }
}
