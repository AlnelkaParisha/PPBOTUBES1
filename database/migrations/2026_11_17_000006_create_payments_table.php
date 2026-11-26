<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu invoice boleh punya banyak percobaan bayar (gagal dulu, lalu berhasil)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method');               // bank_transfer | ewallet | qris | credit_card | credit
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending');   // pending | success | failed
            $table->string('reference')->unique();
            $table->timestamp('paid_at')->nullable();       // terisi hanya jika success
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
