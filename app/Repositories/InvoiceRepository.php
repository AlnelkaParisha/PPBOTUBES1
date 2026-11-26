<?php

namespace App\Repositories;

use App\Contracts\InvoiceRepositoryInterface;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

// Satu-satunya tempat query Invoice
class InvoiceRepository implements InvoiceRepositoryInterface
{
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);

        return $invoice;
    }

    public function findForUpdate(int $id): Invoice
    {
        return Invoice::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function hasRenewalFor(Subscription $subscription): bool
    {
        return Invoice::where('subscription_id', $subscription->id)
            ->where('type', InvoiceType::Renewal->value)
            ->whereDate('period_start', $subscription->period_end)
            ->exists();
    }

    public function forUser(User $user): Collection
    {
        return Invoice::with(['subscription.plan', 'subscription.members', 'payments'])
            ->whereHas('subscription', fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->get();
    }
}
