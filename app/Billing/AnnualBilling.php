<?php

namespace App\Billing;

use App\Models\Plan;
use Carbon\CarbonInterface;

class AnnualBilling extends BaseBilling
{
    private const MONTHS = 12;

    // Persen diskon dibaca dari kolom plans.discount_percent, bukan ditulis di kode
    public function calculateTotal(Plan $plan): int
    {
        $fullPrice = $this->basePrice($plan) * self::MONTHS;
        $discount = max(0, min(100, (int) $plan->discount_percent));

        return intdiv($fullPrice * (100 - $discount), 100);
    }

    public function calculatePeriodEnd(CarbonInterface $start): CarbonInterface
    {
        return $start->copy()->addYearNoOverflow();
    }
}
