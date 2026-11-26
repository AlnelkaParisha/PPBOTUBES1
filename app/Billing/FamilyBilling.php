<?php

namespace App\Billing;

use App\Contracts\SplittableBilling;
use App\Models\Plan;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class FamilyBilling extends BaseBilling implements SplittableBilling
{
    public function calculateTotal(Plan $plan): int
    {
        return $this->basePrice($plan);
    }

    public function calculatePeriodEnd(CarbonInterface $start): CarbonInterface
    {
        return $start->copy()->addMonthNoOverflow();
    }

    public function splitAmongMembers(int $total, int $membersCount): array
    {
        if ($membersCount < 1) {
            throw new InvalidArgumentException('Jumlah anggota minimal 1.');
        }

        $shares = array_fill(0, $membersCount, intdiv($total, $membersCount));
        $remainder = $total % $membersCount;

        for ($i = 0; $i < $remainder; $i++) {
            $shares[$i]++;
        }

        return $shares;
    }
}

