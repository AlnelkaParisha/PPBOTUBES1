<?php

namespace App\Contracts;

use App\Models\Plan;
use Carbon\CarbonInterface;

interface BillingStrategy
{
    public function calculateTotal(Plan $plan): int;

    public function calculatePeriodEnd(CarbonInterface $start): CarbonInterface;

    public function calculateUnusedCredit(
        Plan $plan,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        CarbonInterface $changeDate
    ): int;
}
