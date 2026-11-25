<?php

use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

// Dimuat di dalam grup auth:sanctum (lihat routes/api.php)
Route::get('invoices', [InvoiceController::class, 'index']);
Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
