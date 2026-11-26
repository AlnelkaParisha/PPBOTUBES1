<?php

namespace App\Billing;

use App\Models\Plan;
use Carbon\CarbonInterface;

class MonthlyBilling extends BaseBilling
{
    public function calculateTotal(Plan $plan): int
    {
        return $this->basePrice($plan);
    }

    public function calculatePeriodEnd(CarbonInterface $start): CarbonInterface
    {
        return $start->copy()->addMonthNoOverflow();
    }
}
