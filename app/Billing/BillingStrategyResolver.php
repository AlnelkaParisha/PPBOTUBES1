<?php

namespace App\Billing;

use App\Contracts\BillingStrategy;
use App\Models\Plan;
use BackedEnum;
use InvalidArgumentException;

class BillingStrategyResolver
{
    /** @var array<string, BillingStrategy> */
    private array $strategies;

    /** @param array<string, BillingStrategy> $strategies */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    public function resolve(Plan $plan): BillingStrategy
    {
        $cycle = $plan->billing_cycle;
        $key = $cycle instanceof BackedEnum ? (string) $cycle->value : (string) $cycle;

        if (!isset($this->strategies[$key])) {
            throw new InvalidArgumentException("Siklus billing '{$key}' tidak didukung.");
        }

        return $this->strategies[$key];
    }
}
