<?php

use App\Http\Controllers\Api\PlanController;
use Illuminate\Support\Facades\Route;

// Publik: lihat daftar dan detail paket
Route::apiResource('plans', PlanController::class)->only(['index', 'show']);

// Admin: kelola paket
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('plans', PlanController::class)->only(['store', 'update', 'destroy']);
});
