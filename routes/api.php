<?php

use App\Http\Controllers\PublicReportingController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:public-tracking'])->group(function () {
    Route::get('/categories', [PublicReportingController::class, 'categories']);
    Route::get('/reports/{reference}/status', [TrackingController::class, 'status']);
});

Route::middleware(['throttle:public-report', 'turnstile'])->post('/reports', [PublicReportingController::class, 'store']);
