<?php

namespace App\Billing;

use App\Contracts\BillingStrategy;
use App\Models\Plan;
use Carbon\CarbonInterface;

abstract class BaseBilling implements BillingStrategy
{
    abstract public function calculateTotal(Plan $plan): int;

    abstract public function calculatePeriodEnd(CarbonInterface $start): CarbonInterface;

    public function calculateUnusedCredit(
        Plan $plan,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        CarbonInterface $changeDate
    ): int {
        $totalDays = $this->daysBetween($periodStart, $periodEnd);

        if ($totalDays <= 0) {
            return 0;
        }

        $remainingDays = max(0, min($totalDays, $this->daysBetween($changeDate, $periodEnd)));

        return (int) round($this->calculateTotal($plan) * $remainingDays / $totalDays);
    }

    protected function basePrice(Plan $plan): int
    {
        return max(0, (int) $plan->price);
    }

    protected function daysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $start = $from->copy()->startOfDay()->getTimestamp();
        $end = $to->copy()->startOfDay()->getTimestamp();

        return (int) round(($end - $start) / 86400);
    }
}
