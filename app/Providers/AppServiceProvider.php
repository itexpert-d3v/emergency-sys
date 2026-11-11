<?php

namespace App\Providers;

use App\Models\NewsPost;
use App\Services\Audit\AuditWriter;
use App\Support\I18n\LocaleCatalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditWriter::class, function () {
            return new AuditWriter(DB::connection('mysql_audit'));
        });
    }

    public function boot(): void
    {
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by('auth-ip:' . $request->ip()),
            Limit::perMinute(5)->by('auth-email:' . hash('sha256', strtolower((string) $request->input('email')))),
        ]);

        // Emergency form: failed validation still counts, mobile carriers share IPs,
        // and typhoon days bring many legitimate reports from the same address.
        RateLimiter::for('public-report', fn (Request $request) => [
            Limit::perMinute(10)->by('report-ip:' . $request->ip()),
            Limit::perHour(60)->by('report-ip-hour:' . $request->ip()),
        ]);

        // /track page loads stay per-IP.
        RateLimiter::for('public-tracking', fn (Request $request) =>
            Limit::perMinute(30)->by('tracking-ip:' . $request->ip())
        );

        // GET .../messages polling — key by tracking token, not IP, so one family
        // using two tabs or an office on one Wi-Fi doesn't block each other.
        RateLimiter::for('public-message-read', fn (Request $request) =>
            Limit::perMinute(20)->by('msg-read:' . sha1((string) $request->route('token')))
        );

        // POST .../messages — per-token write cap + per-IP backstop.
        RateLimiter::for('public-message', fn (Request $request) => [
            Limit::perMinute(8)->by('msg-write:' . sha1((string) $request->route('token'))),
            Limit::perMinute(30)->by('msg-write-ip:' . $request->ip()),
        ]);

        // Staff queue polling: ~10 open rows x ~4 polls/min ≈ 40; 60 was too close.
        RateLimiter::for('staff-read', fn (Request $request) =>
            Limit::perMinute(120)->by('staff-read:' . ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for('staff-write', fn (Request $request) =>
            Limit::perMinute(30)->by('staff-write:' . ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for('admin-write', fn (Request $request) =>
            Limit::perMinute(30)->by('admin-write:' . ($request->user()?->id ?? $request->ip()))
        );

        View::composer(['landing', 'public.*', 'tracking.*'], function ($view): void {
            $view->with('availableLocales', app(LocaleCatalog::class)->all());
        });

        // Migrations guarantee the table exists in production, so skip the schema
        // lookup and cache the footer news for 5 minutes.
        View::composer('landing', function ($view): void {
            $view->with('footerNews', Cache::remember('landing.footer_news', 300, fn () =>
                NewsPost::query()
                    ->visibleToPublic()
                    ->orderByDesc('published_at')
                    ->limit(3)
                    ->get(['title', 'slug', 'category', 'published_at'])
            ));
        });
    }
}