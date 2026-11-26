<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Sanctum::actingAs(User::factory()->create());
    }

    private function plan(string $name): Plan
    {
        return Plan::where('name', $name)->firstOrFail();
    }

    public function test_pembayaran_gagal_tercatat_lalu_percobaan_kedua_berhasil(): void
    {
        $this->postJson('/api/subscriptions', ['plan_id' => $this->plan('Basic Bulanan')->id])->assertCreated();
        $invoice = Invoice::firstOrFail();

        $this->postJson("/api/invoices/{$invoice->id}/pay", ['payment_method' => 'qris', 'simulate' => 'failed'])
            ->assertStatus(402);

        $this->assertSame('pending', $invoice->fresh()->status->value);
        $this->assertSame('pending', Subscription::firstOrFail()->status->value);
        $this->assertSame(1, Payment::where('status', 'failed')->count());

        $this->postJson("/api/invoices/{$invoice->id}/pay", ['payment_method' => 'ewallet'])->assertOk();

        $this->assertSame('paid', $invoice->fresh()->status->value);
        $this->assertSame('active', Subscription::firstOrFail()->status->value);
        $this->assertSame(2, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertSame(1, Payment::where('status', 'success')->count());
    }

    public function test_invoice_yang_sudah_lunas_tidak_bisa_dibayar_lagi(): void
    {
        $this->postJson('/api/subscriptions', ['plan_id' => $this->plan('Basic Bulanan')->id])->assertCreated();
        $invoice = Invoice::firstOrFail();

        $this->postJson("/api/invoices/{$invoice->id}/pay", ['payment_method' => 'qris'])->assertOk();
        $this->postJson("/api/invoices/{$invoice->id}/pay", ['payment_method' => 'qris'])->assertStatus(422);

        $this->assertSame(1, Payment::count());
    }

    public function test_metode_credit_tidak_boleh_dipilih_user(): void
    {
        $this->postJson('/api/subscriptions', ['plan_id' => $this->plan('Basic Bulanan')->id])->assertCreated();

        $this->postJson('/api/invoices/'.Invoice::firstOrFail()->id.'/pay', ['payment_method' => 'credit'])
            ->assertStatus(422);
    }

    public function test_family_menyimpan_anggota_dan_tagihan_terbagi_ke_anggota(): void
    {
        $this->postJson('/api/subscriptions', [
            'plan_id' => $this->plan('Family')->id,
            'members' => [
                ['name' => 'Ayah', 'email' => 'ayah@example.com'],
                ['name' => 'Ibu', 'email' => 'ibu@example.com'],
                ['name' => 'Anak', 'email' => 'anak@example.com'],
            ],
        ])->assertCreated()->assertJsonPath('data.members_count', 3);

        $this->assertDatabaseCount('subscription_members', 3);

        $invoice = Invoice::firstOrFail();
        $shares = $this->getJson("/api/invoices/{$invoice->id}")->assertOk()->json('data.member_shares');

        $this->assertCount(3, $shares);
        $this->assertSame(150000, array_sum(array_column($shares, 'share')));
        $this->assertSame('Ayah', $shares[0]['name']);
    }

    public function test_family_tanpa_anggota_ditolak(): void
    {
        $this->postJson('/api/subscriptions', ['plan_id' => $this->plan('Family')->id])->assertStatus(422);
    }

    public function test_diskon_tahunan_diambil_dari_data_paket(): void
    {
        $plan = $this->plan('Premium Tahunan');
        $this->assertSame(20, $plan->discount_percent);

        $plan->update(['discount_percent' => 50]);

        $this->postJson('/api/subscriptions', ['plan_id' => $plan->id])->assertCreated();

        // 100.000 x 12 = 1.200.000, diskon 50 persen = 600.000
        $this->assertSame(600000, Invoice::firstOrFail()->total);
    }

    public function test_keluar_dari_family_menghapus_anggota(): void
    {
        $subscription = $this->postJson('/api/subscriptions', [
            'plan_id' => $this->plan('Family')->id,
            'members' => [
                ['name' => 'Ayah', 'email' => 'ayah@example.com'],
                ['name' => 'Ibu', 'email' => 'ibu@example.com'],
            ],
        ])->assertCreated()->json('data');

        $this->postJson('/api/invoices/'.Invoice::firstOrFail()->id.'/pay', ['payment_method' => 'qris'])->assertOk();

        $this->postJson("/api/subscriptions/{$subscription['id']}/change-plan", [
            'plan_id' => $this->plan('Premium Bulanan')->id,
        ])->assertOk()->assertJsonPath('data.members_count', 1);

        $this->assertDatabaseCount('subscription_members', 0);
    }
}
