<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('type');                                  // initial | renewal | proration
            $table->unsignedInteger('amount');                       // harga sebelum kredit
            $table->unsignedInteger('credit_applied')->default(0);   // kredit yang dipotong
            $table->unsignedInteger('total');                        // yang harus dibayar
            $table->string('status')->default('pending');            // pending | paid | overdue
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            // Metode dan referensi pembayaran SENGAJA tidak di sini: ada di tabel payments
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
