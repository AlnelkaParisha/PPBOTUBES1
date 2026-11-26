<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireUnpaidSubscriptions extends Command
{
    protected $signature = 'billing:expire-unpaid';

    protected $description = 'Tandai invoice lewat jatuh tempo sebagai overdue dan akhiri langganan yang tidak dibayar';

    public function handle(): int
    {
        // 1. Invoice pending yang lewat jatuh tempo menjadi overdue
        $overdue = Invoice::where('status', InvoiceStatus::Pending->value)
            ->whereDate('due_date', '<', today())
            ->update(['status' => InvoiceStatus::Overdue->value]);

        // 2. Langganan dengan invoice overdue menjadi expired
        $unpaid = Subscription::whereIn('status', [SubscriptionStatus::Pending->value, SubscriptionStatus::Active->value])
            ->whereHas('invoices', fn ($q) => $q->where('status', InvoiceStatus::Overdue->value))
            ->update(['status' => SubscriptionStatus::Expired->value]);

        // 3. Langganan aktif atau dibatalkan yang periodenya habis menjadi expired
        $ended = Subscription::whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Cancelled->value])
            ->whereDate('period_end', '<', today())
            ->update(['status' => SubscriptionStatus::Expired->value]);

        $this->info("Invoice overdue: {$overdue}, expired karena tidak bayar: {$unpaid}, expired karena periode habis: {$ended}.");

        return self::SUCCESS;
    }
}
