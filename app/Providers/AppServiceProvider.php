<?php

namespace App\Providers;

use App\Events\LiveMapChanged;
use App\Models\IncidentReport;
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
        IncidentReport::saved(fn () => rescue(fn () => LiveMapChanged::dispatch()));
        IncidentReport::deleted(fn () => rescue(fn () => LiveMapChanged::dispatch()));

        $disabledLimiter = fn (Request $request) => Limit::perMinute(PHP_INT_MAX)->by('progressive-limits-disabled:'.$request->ip());
        $whenEnabled = fn (callable $limiter): callable => config('progressive_rate_limit.enabled')
            ? $limiter
            : $disabledLimiter;

        RateLimiter::for('public-read', $whenEnabled(fn (Request $request) => Limit::perMinute(60)->by('public-read:'.$request->ip())
        ));

        RateLimiter::for('auth', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(5)->by('auth-ip:'.$request->ip()),
            Limit::perMinute(5)->by('auth-email:'.hash('sha256', strtolower((string) $request->input('email')))),
        ]));

        RateLimiter::for('public-report', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(3)->by('report-ip:'.$request->ip()),
            Limit::perHour(60)->by('report-ip-hour:'.$request->ip()),
        ]));

        RateLimiter::for('public-tracking', $whenEnabled(fn (Request $request) => Limit::perMinute(10)->by('tracking-ip:'.$request->ip())
        ));

        /*
         * Public message read.
         * messaging.js polls every 15s → 4 req/min per open tab. 30/min per
         * token lets a reporter keep several tabs open; 60/min per IP is a
         * generous backstop for shared networks.
         */
        RateLimiter::for('public-message-read', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(30)->by('msg-read-token:'.sha1((string) $request->route('token'))),
            Limit::perMinute(60)->by('msg-read-ip:'.$request->ip()),
        ]));

        /*
         * Public message write.
         * 5/min per token stops spam bursts; 30/hr per token stops sustained
         * abuse from one tracking link; 10/min per IP catches attackers who
         * cycle through many tokens they don't own.
         */
        RateLimiter::for('public-message', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(5)->by('msg-write-token:'.sha1((string) $request->route('token'))),
            Limit::perHour(30)->by('msg-write-token-hour:'.sha1((string) $request->route('token'))),
            Limit::perMinute(10)->by('msg-write-ip:'.$request->ip()),
        ]));

        RateLimiter::for('staff-read', $whenEnabled(fn (Request $request) => Limit::perMinute(60)->by('staff-read:'.($request->user()?->id ?? $request->ip()))
        ));

        RateLimiter::for('live-map', $whenEnabled(fn (Request $request) => Limit::perMinute(120)->by('live-map:'.($request->user()?->getAuthIdentifier() ?? $request->ip()))
        ));

        RateLimiter::for('route-directions', $whenEnabled(fn (Request $request) => Limit::perMinute(30)->by('route-directions:'.($request->user()?->getAuthIdentifier() ?? $request->ip()))
        ));

        RateLimiter::for('staff-write', $whenEnabled(fn (Request $request) => Limit::perMinute(30)->by('staff-write:'.($request->user()?->id ?? $request->ip()))
        ));

        /*
         * Staff message write.
         * 20/min per staff user is comfortable for replying to many reports;
         * 60/min per report stops one staff member from flooding a thread.
         */
        RateLimiter::for('staff-message', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(20)->by('staff-msg-user:'.($request->user()?->id ?? $request->ip())),
            Limit::perMinute(60)->by('staff-msg-report:'.(string) $request->route('report')),
        ]));

        RateLimiter::for('staff-message-read', $whenEnabled(fn (Request $request) => Limit::perMinute(120)->by('staff-msg-read:'.($request->user()?->id ?? $request->ip()))
        ));

        RateLimiter::for('admin-write', $whenEnabled(fn (Request $request) => Limit::perMinute(30)->by('admin-write:'.($request->user()?->id ?? $request->ip()))
        ));

        RateLimiter::for('admin-sensitive', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(5)->by('admin-sensitive-user:'.($request->user()?->id ?? $request->ip())),
            Limit::perMinute(10)->by('admin-sensitive-ip:'.$request->ip()),
        ]));

        RateLimiter::for('admin-reports', $whenEnabled(fn (Request $request) => [
            Limit::perMinute(5)->by('admin-reports-user:'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(10)->by('admin-reports-ip:'.$request->ip()),
        ]));

        RateLimiter::for('news-read', $whenEnabled(fn (Request $request) => Limit::perMinute(30)->by('news-read:'.$request->ip())
        ));

        View::composer(['landing', 'public.*', 'tracking.*'], function ($view): void {
            $view->with('availableLocales', app(LocaleCatalog::class)->all());
        });

        View::composer('landing', function ($view): void {
            $view->with('footerNews', Cache::remember('landing.footer_news', 300, fn () => NewsPost::query()
                ->visibleToPublic()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['title', 'slug', 'category', 'published_at'])
            ));
        });

        View::composer('landing', function ($view): void {
            $view->with('recentNews', Cache::remember('landing.recent_news', 300, fn () => NewsPost::query()
                ->visibleToPublic()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['title', 'slug', 'category', 'published_at'])
            ));
        });
    }
}
