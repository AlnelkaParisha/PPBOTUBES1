<?php

namespace App\Services;

use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\PaymentGateway;
use App\Contracts\PaymentRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

// Satu percobaan bayar = satu baris payments. Gagal pun tercatat; invoice tetap pending sampai ada yang sukses.
class PaymentService
{
    public function __construct(
        private PaymentGateway $gateway,
        private PaymentRepositoryInterface $payments,
        private InvoiceRepositoryInterface $invoices,
        private InvoiceService $invoiceService
    ) {
    }

    public function pay(Invoice $invoice, PaymentMethod $method, array $options = []): Payment
    {
        return DB::transaction(function () use ($invoice, $method, $options) {
            // Kunci baris invoice: dua klik "bayar" bersamaan tidak boleh sama-sama lolos
            $locked = $this->invoices->findForUpdate($invoice->id);

            if ($locked->status !== InvoiceStatus::Pending) {
                throw new BusinessRuleException('Invoice ini tidak dalam status menunggu pembayaran.');
            }

            $response = $this->gateway->charge($locked, $method, $options);

            $payment = $this->payments->create([
                'invoice_id' => $locked->id,
                'payment_method' => $method->value,
                'amount' => $locked->total,
                'status' => $response->success ? PaymentStatus::Success->value : PaymentStatus::Failed->value,
                'reference' => $response->reference,
                'paid_at' => $response->success ? now() : null,
            ]);

            if ($response->success) {
                $this->invoiceService->markAsPaid($locked);
            }

            return $payment;
        });
    }
}
