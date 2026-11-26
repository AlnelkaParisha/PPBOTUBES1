<?php

namespace App\Traits;

use App\Models\Invoice;
use Illuminate\Support\Str;

// Trait: kode pembuat nomor invoice unik yang bisa dipakai ulang
trait HasInvoiceNumber
{
    protected function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }
}
