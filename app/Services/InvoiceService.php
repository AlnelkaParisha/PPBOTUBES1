<?php

namespace App\Services;

use App\Billing\BillingService;
use App\Billing\ProrationResult;
use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\PaymentRepositoryInterface;
use App\Contracts\SubscriptionRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Traits\HasInvoiceNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Tugas class ini: MEMBUAT tagihan dan menandainya lunas.
// Proses bayarnya sendiri ada di PaymentService.
class InvoiceService
{
    use HasInvoiceNumber;

    private const DUE_DAYS = 3;

    public function __construct(
        private BillingService $billing,
        private InvoiceRepositoryInterface $invoices,
        private PaymentRepositoryInterface $payments,
        private SubscriptionRepositoryInterface $subscriptions
    ) {
    }

    // Tagihan pertama saat user subscribe
    public function createInitial(Subscription $subscription): Invoice
    {
        $amount = $this->billing->calculateTotal($subscription->plan);

        return $this->invoices->create([
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'type' => InvoiceType::Initial,
            'amount' => $amount,
            'credit_applied' => 0,
            'total' => $amount,
            'status' => InvoiceStatus::Pending,
            'period_start' => $subscription->period_start,
            'period_end' => $subscription->period_end,
            'due_date' => today()->addDays(self::DUE_DAYS),
        ]);
    }

    // Tagihan selisih saat upgrade. Hanya dipanggil jika ada yang harus dibayar.
    public function createProration(Subscription $subscription, ProrationResult $result): Invoice
    {
        return $this->invoices->create([
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'type' => InvoiceType::Proration,
            'amount' => $result->newCharge,
            'credit_applied' => $result->oldCredit,
            'total' => $result->payNow(),
            'status' => InvoiceStatus::Pending,
            'period_start' => $result->newPeriodStart,
            'period_end' => $result->newPeriodEnd,
            'due_date' => today()->addDays(self::DUE_DAYS),
        ]);
    }

    // Tagihan periode berikutnya. Kredit (credit_balance) dipotong lebih dulu.
    public function createRenewal(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;
            $start = $subscription->period_end->copy();
            $amount = $this->billing->calculateTotal($plan);
            $credit = min($subscription->credit_balance, $amount);

            $invoice = $this->invoices->create([
                'subscription_id' => $subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'type' => InvoiceType::Renewal,
                'amount' => $amount,
                'credit_applied' => $credit,
                'total' => $amount - $credit,
                'status' => InvoiceStatus::Pending,
                'period_start' => $start,
                'period_end' => $this->billing->calculatePeriodEnd($plan, $start),
                'due_date' => $subscription->period_end,
            ]);

            if ($credit > 0) {
                $this->subscriptions->update($subscription, [
                    'credit_balance' => $subscription->credit_balance - $credit,
                ]);
            }

            // Lunas penuh oleh kredit: tidak perlu menunggu pembayaran
            if ($invoice->total === 0) {
                return $this->settleWithCredit($invoice);
            }

            return $invoice;
        });
    }

    public function hasRenewalFor(Subscription $subscription): bool
    {
        return $this->invoices->hasRenewalFor($subscription);
    }

    // Dipanggil PaymentService setelah ada pembayaran yang berhasil.
    public function markAsPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = $this->invoices->findForUpdate($invoice->id);

            if ($invoice->status !== InvoiceStatus::Pending) {
                throw new BusinessRuleException('Invoice ini tidak dalam status menunggu pembayaran.');
            }

            $this->invoices->update($invoice, [
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ]);

            // Invoice awal dan perpanjangan mengaktifkan langganan untuk periode invoice tsb.
            // Invoice prorata tidak mengubah periode (sudah diatur saat ganti paket).
            if (in_array($invoice->type, [InvoiceType::Initial, InvoiceType::Renewal], true)) {
                $this->subscriptions->update($invoice->subscription, [
                    'status' => SubscriptionStatus::Active,
                    'period_start' => $invoice->period_start,
                    'period_end' => $invoice->period_end,
                ]);
            }

            return $invoice->fresh();
        });
    }

    // Invoice yang total-nya 0 karena kredit tetap dicatat sebagai payment (metode 'credit')
    private function settleWithCredit(Invoice $invoice): Invoice
    {
        $this->payments->create([
            'invoice_id' => $invoice->id,
            'payment_method' => PaymentMethod::Credit->value,
            'amount' => 0,
            'status' => PaymentStatus::Success->value,
            'reference' => 'CREDIT-'.strtoupper(Str::random(10)),
            'paid_at' => now(),
        ]);

        return $this->markAsPaid($invoice);
    }
}
