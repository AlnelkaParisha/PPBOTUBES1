<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Initial = 'initial';
    case Renewal = 'renewal';
    case Proration = 'proration';
}
