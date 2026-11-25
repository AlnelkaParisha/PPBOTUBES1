<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'billing:generate-invoices';

    protected $description = 'Buat invoice perpanjangan untuk langganan aktif yang periodenya habis dalam 3 hari';

    public function handle(InvoiceService $invoices): int
    {
        $count = 0;

        Subscription::with('plan')
            ->where('status', SubscriptionStatus::Active->value)
            ->whereDate('period_end', '<=', today()->addDays(3))
            ->each(function (Subscription $subscription) use ($invoices, &$count) {
                if ($invoices->hasRenewalFor($subscription)) {
                    return;
                }

                $invoices->createRenewal($subscription);
                $count++;
            });

        $this->info("{$count} invoice perpanjangan dibuat.");

        return self::SUCCESS;
    }
}
