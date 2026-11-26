<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Support\Str;

// Payment gateway PALSU untuk tugas besar: tidak ada uang sungguhan dan tidak memanggil internet.
// Hasil selalu sukses, kecuali options['simulate'] = 'failed' (dipakai untuk menguji pembayaran gagal).
class SimulatedPaymentGateway implements PaymentGateway
{
    public function charge(Invoice $invoice, PaymentMethod $method, array $options = []): GatewayResponse
    {
        $reference = 'SIM-'.strtoupper(Str::random(10));

        if (($options['simulate'] ?? 'success') === 'failed') {
            return new GatewayResponse(false, $reference, 'Pembayaran ditolak (simulasi).');
        }

        return new GatewayResponse(true, $reference);
    }
}
