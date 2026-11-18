<?php

use App\Http\Controllers\PublicReportingController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\BlockBannedIp;
use App\Http\Middleware\BlockBannedPhone;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:public-tracking'])->group(function () {
    Route::get('/categories', [PublicReportingController::class, 'categories']);
    Route::get('/reports/{reference}/status', [TrackingController::class, 'status']);
});

Route::middleware(['throttle:public-report', BlockBannedIp::class, BlockBannedPhone::class, 'turnstile'])->post('/reports', [PublicReportingController::class, 'store']);
