<?php

use Illuminate\Support\Facades\Route;

// File ini milik Nelka. Tiap modul punya file route sendiri supaya tidak bentrok di Git.
// File hanya dimuat jika sudah ada, jadi aplikasi tetap jalan walau modul lain belum di-merge.
foreach (['auth', 'plans'] as $module) {
    if (is_file(__DIR__."/api/{$module}.php")) {
        require __DIR__."/api/{$module}.php";
    }
}

Route::middleware('auth:sanctum')->group(function () {
    foreach (['subscriptions', 'invoices', 'contents'] as $module) {
        if (is_file(__DIR__."/api/{$module}.php")) {
            require __DIR__."/api/{$module}.php";
        }
    }
});
