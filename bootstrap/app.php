<?php

use App\Http\Middleware\EnsureCorrelationId;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\SetPublicLocale;
use App\Http\Middleware\VerifyTurnstile;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Fix #1: behind Nginx/Cloudflare, $request->ip() is the proxy address
        // unless trusted proxies are configured. Every visitor would share one
        // rate-limit bucket otherwise.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '::1',
            // Add Cloudflare ranges here if applicable, e.g.:
            // '173.245.48.0/20', '103.21.244.0/22', ...
            // Or, to trust the whole edge:  at: '*'
        ]);

        $middleware->append(EnsureCorrelationId::class);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin/*')
            ? route('admin.login')
            : route('staff.login'));

        $middleware->alias([
            'turnstile' => VerifyTurnstile::class,
            'role' => EnsureUserRole::class,
            'public.locale' => SetPublicLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();