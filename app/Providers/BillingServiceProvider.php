<?php

namespace App\Providers;

use App\Billing\AnnualBilling;
use App\Billing\BillingStrategyResolver;
use App\Billing\FamilyBilling;
use App\Billing\MonthlyBilling;
use App\Contracts\PaymentGateway;
use App\Enums\BillingCycle;
use App\Payments\SimulatedPaymentGateway;
use Illuminate\Support\ServiceProvider;

// Dependency Injection: daftar strategy dirakit di sini, BillingService menerimanya lewat constructor
class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BillingStrategyResolver::class, function () {
            return new BillingStrategyResolver([
                BillingCycle::Monthly->value => new MonthlyBilling(),
                BillingCycle::Annual->value => new AnnualBilling(),
                BillingCycle::Family->value => new FamilyBilling(),
            ]);
        });

        // Pembayaran: sekarang simulasi. Mau pakai gateway asli, ganti class di baris ini saja.
        $this->app->bind(PaymentGateway::class, SimulatedPaymentGateway::class);
    }
}
