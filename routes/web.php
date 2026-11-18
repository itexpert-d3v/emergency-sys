<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\AdminModerationController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PublicReportingController;
use App\Http\Controllers\ReportMessageController;
use App\Http\Controllers\ReportPhotoController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TrackingController;
use App\Http\Middleware\SetPublicLocale;
use App\Http\Middleware\BlockBannedIp;
use App\Http\Middleware\BlockBannedPhone;
use App\Models\NewsPost;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
})->middleware('throttle:public-read');

Route::post('/language', [LocaleController::class, 'update'])->middleware('throttle:public-read')->name('locale.update');

Route::view('/about', 'public.about')->middleware([SetPublicLocale::class, 'throttle:public-read'])->name('about');
Route::view('/how-it-works', 'public.how-it-works')->middleware([SetPublicLocale::class, 'throttle:public-read'])->name('how-it-works');

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

    $recentNews = NewsPost::query()
        ->visibleToPublic()
        ->orderByDesc('published_at')
        ->limit(3)
        ->get(['title', 'slug', 'category', 'published_at']);

    return view('landing', [
        'incidents' => $incidents,
        'filters' => $filters,
        'categories' => array_values(array_unique(array_column($demoIncidents, 'category'))),
        'statuses' => array_values(array_unique(array_column($demoIncidents, 'status'))),
        'recentNews' => $recentNews,
    ]);
})->middleware([SetPublicLocale::class, 'throttle:public-read'])->name('landing');

Route::get('/report', [PublicReportingController::class, 'create'])->middleware([SetPublicLocale::class, 'throttle:public-read'])->name('public.report');
Route::post('/report', [PublicReportingController::class, 'storeFromForm'])
    ->middleware([SetPublicLocale::class, 'throttle:public-report', BlockBannedIp::class, BlockBannedPhone::class, 'turnstile'])
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

Route::middleware([SetPublicLocale::class])->group(function () {
    Route::get('/track/{token}/messages', [ReportMessageController::class, 'reporterIndex'])
        ->middleware('throttle:public-message-read')
        ->name('track.messages');
    Route::post('/track/{token}/messages', [ReportMessageController::class, 'reporterStore'])
        ->middleware('throttle:public-message')
        ->name('track.messages.store');
});

Route::get('/login', [SessionController::class, 'create'])->middleware('throttle:public-read')->name('login');
Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:auth');
Route::post('/logout', [SessionController::class, 'destroy'])->middleware(['auth', 'throttle:staff-write'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('throttle:staff-read')
        ->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:staff-write')
        ->name('profile.update');

    Route::middleware(['role:staff,admin', 'throttle:staff-read'])->group(function () {
        Route::get('/queue', [StaffDashboardController::class, 'index'])->name('operations.queue');
        Route::get('/queue/poll', [StaffDashboardController::class, 'poll'])->name('operations.queue.poll');
        Route::get('/reports/{report}/messages', [ReportMessageController::class, 'staffIndex'])->name('operations.report.messages');
        Route::get('/reports/{report}/photo', [ReportPhotoController::class, 'forStaff'])->name('operations.report.photo');
    });
    Route::middleware(['role:staff,admin', 'throttle:staff-write'])->group(function () {
        Route::post('/reports/{report}/triage', [StaffDashboardController::class, 'triage'])->name('operations.report.triage');
        Route::post('/reports/{report}/messages', [ReportMessageController::class, 'staffStore'])->name('operations.report.messages.store');
    });

    Route::middleware(['role:admin,staff,mdrrmo_officer,information_officer', 'throttle:staff-read'])->group(function () {
        Route::get('/admin/news', [AdminNewsController::class, 'index'])->name('admin.news.index');
        Route::get('/admin/news/create', [AdminNewsController::class, 'create'])->name('admin.news.create');
    });
    Route::middleware(['role:admin,staff,mdrrmo_officer,information_officer', 'throttle:admin-write'])->group(function () {
        Route::post('/admin/news', [AdminNewsController::class, 'store'])->name('admin.news.store');
        Route::get('/admin/news/{post}/edit', [AdminNewsController::class, 'edit'])->name('admin.news.edit');
        Route::put('/admin/news/{post}', [AdminNewsController::class, 'update'])->name('admin.news.update');
        Route::patch('/admin/news/{post}/status', [AdminNewsController::class, 'setStatus'])->name('admin.news.status');
        Route::patch('/admin/news/{post}/unarchive', [AdminNewsController::class, 'unarchive'])->name('admin.news.unarchive');
    });

    Route::middleware(['role:admin,staff', 'throttle:staff-read'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('operations.dashboard');
        Route::get('/live-map/alarm-audio', [AdminSettingsController::class, 'alarmAudio'])->name('operations.live-map.alarm-audio');
        Route::get('/live-map', function () {
            return view('admin.live-map', [
                'alarmAudioUrl' => Setting::get('admin.alarm_audio_path') ? route('operations.live-map.alarm-audio') : null,
                'queueUrl' => auth()->user()->role === 'admin' ? route('admin.dashboard.queue') : route('operations.queue'),
            ]);
        })->name('operations.live-map');
        Route::get('/live-map/data', [AdminDashboardController::class, 'liveMap'])->name('operations.live-map.data');
    });
    Route::middleware(['role:admin', 'throttle:staff-read'])->group(function () {
        Route::get('/admin/dashboard/queue', [AdminDashboardController::class, 'queue'])->name('admin.dashboard.queue');
        Route::get('/admin/settings', [AdminSettingsController::class, 'index'])->name('admin.settings');
        Route::get('/admin/moderation', [AdminModerationController::class, 'index'])->name('admin.moderation.index');
        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [UserManagementController::class, 'create'])->name('admin.users.create');
        Route::get('/admin/users/{user}/edit', [UserManagementController::class, 'edit'])->name('admin.users.edit');
        Route::get('/admin/reports', [ReportingController::class, 'index'])->name('admin.reports.index');
        Route::get('/admin/reports/export/{format}', [ReportingController::class, 'export'])
            ->whereIn('format', ['pdf', 'xlsx', 'docx'])
            ->middleware('throttle:admin-reports')
            ->name('admin.reports.export');
    });
    Route::middleware(['role:admin', 'throttle:admin-sensitive'])->group(function () {
        Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    });
    Route::middleware(['role:admin', 'throttle:admin-sensitive'])->group(function () {
        Route::post('/admin/settings/report-intake', [AdminSettingsController::class, 'updateReportSettings'])->name('admin.settings.report-intake.update');
        Route::post('/admin/settings/alarm-audio', [AdminSettingsController::class, 'storeAlarmAudio'])->name('admin.settings.alarm-audio.store');
        Route::delete('/admin/settings/alarm-audio', [AdminSettingsController::class, 'removeAlarmAudio'])->name('admin.settings.alarm-audio.destroy');
        Route::post('/admin/reports/{report}/accept', [AdminDashboardController::class, 'acceptReport'])->name('admin.report.accept');
        Route::post('/admin/ip-bans', [AdminDashboardController::class, 'banIp'])->name('admin.ip-bans.store');
        Route::delete('/admin/ip-bans/{bannedIp}', [AdminDashboardController::class, 'unbanIp'])->name('admin.ip-bans.destroy');
        Route::post('/admin/phone-bans', [AdminDashboardController::class, 'banPhone'])->name('admin.phone-bans.store');
        Route::delete('/admin/phone-bans/{bannedPhone}', [AdminDashboardController::class, 'unbanPhone'])->name('admin.phone-bans.destroy');
        Route::delete('/admin/reports/{report}/ip-ban', [AdminDashboardController::class, 'unbanReporterIp'])->name('admin.report.ip-ban.destroy');
        Route::post('/admin/reports/{report}/soft-delete', [AdminModerationController::class, 'softDelete'])->name('admin.report.soft-delete');
        Route::post('/admin/reports/{report}/reject', [AdminModerationController::class, 'reject'])->name('admin.report.reject');
        Route::post('/admin/moderation/reports/{reportId}/reopen', [AdminModerationController::class, 'reopen'])->name('admin.report.reopen');
        Route::post('/admin/moderation/reports/{reportId}/restore', [AdminModerationController::class, 'restore'])->name('admin.report.restore');
        Route::post('/admin/reports/{report}/dispatch', [AdminDashboardController::class, 'dispatchResponder'])
            ->name('admin.report.dispatch');
    });
});