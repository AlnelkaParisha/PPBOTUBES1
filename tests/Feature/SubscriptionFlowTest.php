<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_subscribe_bayar_lalu_bisa_menonton(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $basic = Plan::where('name', 'Basic Bulanan')->first();
        $hd = Content::where('title', 'Film HD Contoh')->first();

        $this->getJson("/api/contents/{$hd->id}/watch")->assertForbidden();

        $this->postJson('/api/subscriptions', ['plan_id' => $basic->id])->assertCreated();
        $invoice = Invoice::first();
        $this->assertSame(50000, $invoice->total);

        $this->postJson("/api/invoices/{$invoice->id}/pay", ['payment_method' => 'qris'])->assertOk();

        $this->getJson("/api/contents/{$hd->id}/watch")->assertOk();
    }

    public function test_paket_basic_tidak_bisa_menonton_konten_4k(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $basic = Plan::where('name', 'Basic Bulanan')->first();
        $uhd = Content::where('title', 'Film 4K Contoh')->first();

        $this->postJson('/api/subscriptions', ['plan_id' => $basic->id])->assertCreated();
        $this->postJson('/api/invoices/'.Invoice::first()->id.'/pay', ['payment_method' => 'qris'])->assertOk();

        $this->getJson("/api/contents/{$uhd->id}/watch")->assertForbidden();
    }

    public function test_tidak_boleh_punya_dua_langganan_sekaligus(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $basic = Plan::where('name', 'Basic Bulanan')->first();

        $this->postJson('/api/subscriptions', ['plan_id' => $basic->id])->assertCreated();
        $this->postJson('/api/subscriptions', ['plan_id' => $basic->id])->assertStatus(422);
    }

    public function test_upgrade_membuat_invoice_prorata(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $basic = Plan::where('name', 'Basic Bulanan')->first();
        $premium = Plan::where('name', 'Premium Bulanan')->first();

        $subscription = $this->postJson('/api/subscriptions', ['plan_id' => $basic->id])->json('data');
        $this->postJson('/api/invoices/'.Invoice::first()->id.'/pay', ['payment_method' => 'qris'])->assertOk();

        $this->postJson("/api/subscriptions/{$subscription['id']}/change-plan", ['plan_id' => $premium->id])->assertOk();

        // Diganti di hari yang sama: kredit lama penuh 50.000, tagihan baru 100.000, selisih 50.000
        $proration = Invoice::where('type', 'proration')->first();
        $this->assertSame(50000, $proration->total);
    }

    public function test_user_lain_tidak_boleh_mengubah_langganan_orang(): void
    {
        $basic = Plan::where('name', 'Basic Bulanan')->first();

        Sanctum::actingAs(User::factory()->create());
        $subscription = $this->postJson('/api/subscriptions', ['plan_id' => $basic->id])->json('data');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/subscriptions/{$subscription['id']}/cancel")->assertForbidden();
    }

    public function test_endpoint_admin_ditolak_untuk_user_biasa(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/plans', ['name' => 'X', 'price' => 1000, 'billing_cycle' => 'monthly'])->assertForbidden();
    }
}
