<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use App\Models\Content;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // is_admin sengaja tidak mass-assignable, jadi dipakai forceFill
        (new User())->forceFill([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_admin' => true,
        ])->save();

        User::create([
            'name' => 'User Contoh',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        // price = harga per bulan (Rupiah). Paket tahunan: 12 bulan dikurangi discount_percent (disimpan di tabel plans).
        $basic = Plan::create(['name' => 'Basic Bulanan', 'price' => 50000, 'billing_cycle' => BillingCycle::Monthly]);
        $premium = Plan::create(['name' => 'Premium Bulanan', 'price' => 100000, 'billing_cycle' => BillingCycle::Monthly]);
        $premiumYear = Plan::create(['name' => 'Premium Tahunan', 'price' => 1200000, 'billing_cycle' => BillingCycle::Annual, 'discount_percent' => 20]);
        $family = Plan::create(['name' => 'Family', 'price' => 150000, 'billing_cycle' => BillingCycle::Family]);

        $hd = Content::create(['title' => 'Film HD Contoh', 'type' => 'movie', 'description' => 'Tersedia di semua paket']);
        $hd->plans()->attach([$basic->id, $premium->id, $premiumYear->id, $family->id]);

        $uhd = Content::create(['title' => 'Film 4K Contoh', 'type' => 'movie', 'description' => 'Hanya paket Premium dan Family']);
        $uhd->plans()->attach([$premium->id, $premiumYear->id, $family->id]);
    }
}
