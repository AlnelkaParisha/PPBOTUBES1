<?php

use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Dimuat di dalam grup auth:sanctum (lihat routes/api.php)
Route::get('subscriptions', [SubscriptionController::class, 'index']);
Route::post('subscriptions', [SubscriptionController::class, 'store']);
Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show']);
Route::post('subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan']);
Route::post('subscriptions/{subscription}/preview-change', [SubscriptionController::class, 'previewChange']);
Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);