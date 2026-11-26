<?php

use App\Http\Middleware\EnsureCorrelationId;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\PrivateAuthenticatedResponse;
use App\Http\Middleware\ProgressiveRateLimit;
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
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', '127.0.0.1,::1'));
        $trustedProxies = $trustedProxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));
        $middleware->trustProxies(at: $trustedProxies);

        $middleware->append(EnsureCorrelationId::class);
        $middleware->append(PrivateAuthenticatedResponse::class);
        $middleware->appendToGroup('web', ProgressiveRateLimit::class);
        $middleware->appendToGroup('api', ProgressiveRateLimit::class);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));

        $middleware->alias([
            'turnstile' => VerifyTurnstile::class,
            'role' => EnsureUserRole::class,
            'public.locale' => SetPublicLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
