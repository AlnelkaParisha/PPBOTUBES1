<?php

namespace App\Billing;

use App\Contracts\SplittableBilling;
use App\Models\Plan;
use Carbon\CarbonInterface;

class BillingService
{
    private BillingStrategyResolver $resolver;

    public function __construct(BillingStrategyResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function calculateTotal(Plan $plan): int
    {
        return $this->resolver->resolve($plan)->calculateTotal($plan);
    }

    public function calculatePeriodEnd(Plan $plan, CarbonInterface $start): CarbonInterface
    {
        return $this->resolver->resolve($plan)->calculatePeriodEnd($start);
    }

    public function calculateProration(
        Plan $oldPlan,
        Plan $newPlan,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        CarbonInterface $changeDate
    ): ProrationResult {
        $oldStrategy = $this->resolver->resolve($oldPlan);
        $newStrategy = $this->resolver->resolve($newPlan);

        return new ProrationResult(
            $oldStrategy->calculateUnusedCredit($oldPlan, $periodStart, $periodEnd, $changeDate),
            $newStrategy->calculateTotal($newPlan),
            $changeDate->copy(),
            $newStrategy->calculatePeriodEnd($changeDate)
        );
    }

    /** @return int[] */
    public function splitAmount(Plan $plan, int $amount, int $membersCount): array
    {
        $strategy = $this->resolver->resolve($plan);

        if ($strategy instanceof SplittableBilling) {
            return $strategy->splitAmongMembers($amount, $membersCount);
        }

        return [$amount];
    }
}
