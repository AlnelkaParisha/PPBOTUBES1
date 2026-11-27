<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Annual = 'annual';
    case Family = 'family';
}
