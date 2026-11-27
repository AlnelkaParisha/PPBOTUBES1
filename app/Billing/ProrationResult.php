<?php

namespace App\Billing;

use Carbon\CarbonInterface;

final class ProrationResult
{
    public function __construct(
        public readonly int $oldCredit,
        public readonly int $newCharge,
        public readonly CarbonInterface $newPeriodStart,
        public readonly CarbonInterface $newPeriodEnd
    ) {
    }

    public function amountDue(): int
    {
        return $this->newCharge - $this->oldCredit;
    }

    public function payNow(): int
    {
        return max(0, $this->amountDue());
    }

    public function creditBalance(): int
    {
        return max(0, -$this->amountDue());
    }
}
