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
        RateLimiter::for('public-read', fn (Request $request) =>
            Limit::perMinute(60)->by('public-read:' . $request->ip())
        );

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by('auth-ip:' . $request->ip()),
            Limit::perMinute(5)->by('auth-email:' . hash('sha256', strtolower((string) $request->input('email')))),
        ]);

        RateLimiter::for('public-report', fn (Request $request) => [
            Limit::perMinute(3)->by('report-ip:' . $request->ip()),
            Limit::perHour(60)->by('report-ip-hour:' . $request->ip()),
        ]);

        RateLimiter::for('public-tracking', fn (Request $request) =>
            Limit::perMinute(10)->by('tracking-ip:' . $request->ip())
        );

        RateLimiter::for('public-message-read', fn (Request $request) =>
            Limit::perMinute(20)->by('msg-read:' . sha1((string) $request->route('token')))
        );

        RateLimiter::for('public-message', fn (Request $request) => [
            Limit::perMinute(8)->by('msg-write:' . sha1((string) $request->route('token'))),
            Limit::perMinute(10)->by('msg-write-ip:' . $request->ip()),
        ]);

        RateLimiter::for('staff-read', fn (Request $request) =>
            Limit::perMinute(60)->by('staff-read:' . ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for('staff-write', fn (Request $request) =>
            Limit::perMinute(30)->by('staff-write:' . ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for('admin-write', fn (Request $request) =>
            Limit::perMinute(30)->by('admin-write:' . ($request->user()?->id ?? $request->ip()))
        );

        RateLimiter::for('admin-sensitive', fn (Request $request) => [
            Limit::perMinute(5)->by('admin-sensitive-user:' . ($request->user()?->id ?? $request->ip())),
            Limit::perMinute(10)->by('admin-sensitive-ip:' . $request->ip()),
        ]);

        RateLimiter::for('admin-reports', fn (Request $request) => [
            Limit::perMinute(5)->by('admin-reports-user:' . ($request->user()?->id ?? $request->ip())),
            Limit::perHour(10)->by('admin-reports-ip:' . $request->ip()),
        ]);

        View::composer(['landing', 'public.*', 'tracking.*'], function ($view): void {
            $view->with('availableLocales', app(LocaleCatalog::class)->all());
        });

        View::composer('landing', function ($view): void {
            $view->with('footerNews', Cache::remember('landing.footer_news', 300, fn () =>
                NewsPost::query()
                    ->visibleToPublic()
                    ->orderByDesc('published_at')
                    ->limit(3)
                    ->get(['title', 'slug', 'category', 'published_at'])
            ));
        });

        View::composer('landing', function ($view): void {
            $view->with('recentNews', Cache::remember('landing.recent_news', 300, fn () =>
                NewsPost::query()
                    ->visibleToPublic()
                    ->orderByDesc('published_at')
                    ->limit(3)
                    ->get(['title', 'slug', 'category', 'published_at'])
            ));
        });
    }
}