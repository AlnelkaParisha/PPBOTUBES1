<?php

use App\Http\Controllers\Api\ContentController;
use Illuminate\Support\Facades\Route;

// Dimuat di dalam grup auth:sanctum (lihat routes/api.php)
Route::get('contents', [ContentController::class, 'index']);
Route::get('contents/{content}', [ContentController::class, 'show']);
Route::get('contents/{content}/watch', [ContentController::class, 'watch']);

Route::middleware('admin')->group(function () {
    Route::post('contents', [ContentController::class, 'store']);
    Route::put('contents/{content}', [ContentController::class, 'update']);
    Route::delete('contents/{content}', [ContentController::class, 'destroy']);
});
