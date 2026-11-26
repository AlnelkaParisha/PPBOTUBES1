<?php

namespace Tests\Unit\Billing;

use App\Billing\AnnualBilling;
use App\Billing\BillingService;
use App\Billing\BillingStrategyResolver;
use App\Billing\FamilyBilling;
use App\Billing\MonthlyBilling;
use App\Enums\BillingCycle;
use App\Models\Plan;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BillingServiceTest extends TestCase
{
    private BillingService $service;

    protected function setUp(): void
    {
        $this->service = new BillingService(new BillingStrategyResolver([
            'monthly' => new MonthlyBilling(),
            'annual' => new AnnualBilling(),
            'family' => new FamilyBilling(),
        ]));
    }

    private function plan(int $price, BillingCycle $cycle, int $discount = 0): Plan
    {
        $plan = new Plan();
        $plan->price = $price;
        $plan->billing_cycle = $cycle;
        $plan->discount_percent = $discount;

        return $plan;
    }

    public function test_total_tiap_strategy(): void
    {
        $this->assertSame(100000, $this->service->calculateTotal($this->plan(100000, BillingCycle::Monthly)));
        $this->assertSame(960000, $this->service->calculateTotal($this->plan(100000, BillingCycle::Annual, 20)));
        $this->assertSame(150000, $this->service->calculateTotal($this->plan(150000, BillingCycle::Family)));
    }

    public function test_diskon_tahunan_dibaca_dari_data_paket(): void
    {
        $this->assertSame(1200000, $this->service->calculateTotal($this->plan(100000, BillingCycle::Annual, 0)));
        $this->assertSame(600000, $this->service->calculateTotal($this->plan(100000, BillingCycle::Annual, 50)));
    }

    public function test_akhir_periode_tidak_meluber_ke_bulan_berikutnya(): void
    {
        $end = $this->service->calculatePeriodEnd($this->plan(1, BillingCycle::Monthly), Carbon::parse('2026-01-31'));
        $this->assertSame('2026-02-28', $end->toDateString());
    }

    public function test_family_dibagi_rata_dan_jumlahnya_tetap(): void
    {
        $shares = $this->service->splitAmount($this->plan(100000, BillingCycle::Family), 100000, 3);
        $this->assertSame([33334, 33333, 33333], $shares);
        $this->assertSame(100000, array_sum($shares));
    }

    public function test_upgrade_di_tengah_periode(): void
    {
        $result = $this->service->calculateProration(
            $this->plan(100000, BillingCycle::Monthly),
            $this->plan(150000, BillingCycle::Monthly),
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-12-01'),
            Carbon::parse('2026-11-16')
        );

        $this->assertSame(50000, $result->oldCredit);
        $this->assertSame(100000, $result->payNow());
        $this->assertSame('2026-12-16', $result->newPeriodEnd->toDateString());
    }

    public function test_downgrade_menjadi_kredit(): void
    {
        $result = $this->service->calculateProration(
            $this->plan(100000, BillingCycle::Monthly),
            $this->plan(40000, BillingCycle::Monthly),
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-12-01'),
            Carbon::parse('2026-11-16')
        );

        $this->assertSame(0, $result->payNow());
        $this->assertSame(10000, $result->creditBalance());
    }

    public function test_pindah_beda_siklus_monthly_ke_annual(): void
    {
        $result = $this->service->calculateProration(
            $this->plan(100000, BillingCycle::Monthly),
            $this->plan(100000, BillingCycle::Annual, 20),
            Carbon::parse('2026-11-01'),
            Carbon::parse('2026-12-01'),
            Carbon::parse('2026-11-16')
        );

        $this->assertSame(910000, $result->payNow());
        $this->assertSame('2027-11-16', $result->newPeriodEnd->toDateString());
    }

    public function test_siklus_tidak_dikenal_melempar_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new BillingStrategyResolver([]))->resolve($this->plan(1, BillingCycle::Monthly));
    }
}
