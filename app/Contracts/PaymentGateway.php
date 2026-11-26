<?php

namespace App\Contracts;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Payments\GatewayResponse;

// Pintu ke "penyedia pembayaran". Sekarang diisi simulasi; nanti bisa diganti Midtrans/Xendit
// tanpa mengubah PaymentService (cukup ganti binding di BillingServiceProvider).
interface PaymentGateway
{
    public function charge(Invoice $invoice, PaymentMethod $method, array $options = []): GatewayResponse;
}
