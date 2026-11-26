<?php

namespace App\Contracts;

interface SplittableBilling
{
    /** @return int[] */
    public function splitAmongMembers(int $total, int $membersCount): array;
}
