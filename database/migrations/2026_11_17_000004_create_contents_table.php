<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type'); // movie | series | music
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('content_plan', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->primary(['content_id', 'plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_plan');
        Schema::dropIfExists('contents');
    }
};
