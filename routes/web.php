<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PublicReportingController;
use App\Http\Controllers\ReportMessageController;
use App\Http\Controllers\ReportPhotoController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\SetPublicLocale;
use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/language', [LocaleController::class, 'update'])->name('locale.update');

Route::view('/about', 'public.about')->middleware(SetPublicLocale::class)->name('about');
Route::view('/how-it-works', 'public.how-it-works')->middleware(SetPublicLocale::class)->name('how-it-works');

Route::get('/', function () {
    $demoIncidents = [
        ['id' => 'demo-flood', 'category' => 'Flood', 'location' => 'Example barangay', 'reported_at' => 'Demonstration timestamp', 'status' => 'Pending', 'description' => 'Sample content only, included to show how a public update may be presented.'],
        ['id' => 'demo-fire', 'category' => 'Fire', 'location' => 'Example roadside', 'reported_at' => 'Demonstration timestamp', 'status' => 'Under Review', 'description' => 'Sample content only, included to show how a public update may be presented.'],
        ['id' => 'demo-road', 'category' => 'Road Accident', 'location' => 'Example junction', 'reported_at' => 'Demonstration timestamp', 'status' => 'In Progress', 'description' => 'Sample content only, included to show how a public update may be presented.'],
        ['id' => 'demo-medical', 'category' => 'Medical Emergency', 'location' => 'Example community area', 'reported_at' => 'Demonstration timestamp', 'status' => 'Resolved', 'description' => 'Sample content only, included to show how a public update may be presented.'],
    ];
    $filters = [
        'q' => trim((string) request('q', '')),
        'category' => (string) request('category', ''),
        'status' => (string) request('status', ''),
    ];
    $incidents = array_values(array_filter($demoIncidents, function (array $incident) use ($filters): bool {
        $matchesSearch = $filters['q'] === '' || str_contains(strtolower(implode(' ', $incident)), strtolower($filters['q']));
        $matchesCategory = $filters['category'] === '' || $incident['category'] === $filters['category'];
        $matchesStatus = $filters['status'] === '' || $incident['status'] === $filters['status'];

        return $matchesSearch && $matchesCategory && $matchesStatus;
    }));

    return view('landing', [
        'incidents' => $incidents,
        'filters' => $filters,
        'categories' => array_values(array_unique(array_column($demoIncidents, 'category'))),
        'statuses' => array_values(array_unique(array_column($demoIncidents, 'status'))),
    ]);
})->middleware(SetPublicLocale::class)->name('landing');

Route::get('/report', [PublicReportingController::class, 'create'])->middleware(SetPublicLocale::class)->name('public.report');
Route::post('/report', [PublicReportingController::class, 'storeFromForm'])
    ->middleware([SetPublicLocale::class, 'throttle:public-report', 'turnstile'])
    ->name('public.report.store');

Route::middleware([SetPublicLocale::class, 'throttle:30,1'])->group(function () {
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}/attachments/{attachment}', [NewsController::class, 'attachment'])
        ->whereNumber('attachment')
        ->name('news.attachment');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
});

Route::middleware([SetPublicLocale::class, 'throttle:public-tracking'])->group(function () {
    Route::get('/track', [TrackingController::class, 'show'])->name('track');
    Route::get('/track/{token}', [TrackingController::class, 'showByToken'])->name('track.token');
    Route::get('/track/{token}/photo', [ReportPhotoController::class, 'forReporter'])->name('track.photo');
});

// Reporter message endpoints: keyed per tracking token so a shared Wi-Fi
// doesn't throttle unrelated families.
Route::middleware([SetPublicLocale::class])->group(function () {
    Route::get('/track/{token}/messages', [ReportMessageController::class, 'reporterIndex'])
        ->middleware('throttle:public-message-read')
        ->name('track.messages');
    Route::post('/track/{token}/messages', [ReportMessageController::class, 'reporterStore'])
        ->middleware('throttle:public-message')
        ->name('track.messages.store');
});

Route::get('/staff/login', [StaffAuthController::class, 'showLoginForm'])->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/staff/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:staff,admin', 'throttle:staff-read'])->group(function () {
        Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
        Route::get('/staff/dashboard/poll', [StaffDashboardController::class, 'poll'])->name('staff.dashboard.poll');
        Route::get('/staff/reports/{report}/messages', [ReportMessageController::class, 'staffIndex'])->name('staff.report.messages');
        Route::get('/staff/reports/{report}/photo', [ReportPhotoController::class, 'forStaff'])->name('staff.report.photo');
    });
    Route::middleware(['role:staff,admin', 'throttle:staff-write'])->group(function () {
        Route::post('/staff/reports/{report}/triage', [StaffDashboardController::class, 'triage'])->name('staff.report.triage');
        Route::post('/staff/reports/{report}/messages', [ReportMessageController::class, 'staffStore'])->name('staff.report.messages.store');
    });

    Route::middleware(['role:admin,mdrrmo_officer,information_officer', 'throttle:staff-read'])->group(function () {
        Route::get('/admin/news', [AdminNewsController::class, 'index'])->name('admin.news.index');
        Route::get('/admin/news/create', [AdminNewsController::class, 'create'])->name('admin.news.create');
    });
    Route::middleware(['role:admin,mdrrmo_officer,information_officer', 'throttle:admin-write'])->group(function () {
        Route::post('/admin/news', [AdminNewsController::class, 'store'])->name('admin.news.store');
        Route::get('/admin/news/{post}/edit', [AdminNewsController::class, 'edit'])->name('admin.news.edit');
        Route::put('/admin/news/{post}', [AdminNewsController::class, 'update'])->name('admin.news.update');
        Route::patch('/admin/news/{post}/status', [AdminNewsController::class, 'setStatus'])->name('admin.news.status');
    });

    Route::middleware(['role:admin', 'throttle:staff-read'])->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/dashboard/queue', [AdminDashboardController::class, 'queue'])->name('admin.dashboard.queue');
    });
    Route::middleware(['role:admin', 'throttle:admin-write'])->group(function () {
        Route::post('/admin/reports/{report}/dispatch', [AdminDashboardController::class, 'dispatchResponder'])
            ->name('admin.report.dispatch');
    });
});