<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceRepositoryInterface
{
    public function create(array $data): Invoice;

    public function update(Invoice $invoice, array $data): Invoice;

    // Ambil invoice dengan kunci baris, supaya dua pembayaran serentak tidak lolos bersamaan
    public function findForUpdate(int $id): Invoice;

    public function hasRenewalFor(Subscription $subscription): bool;

    public function forUser(User $user): Collection;
}
