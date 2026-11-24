<?php

namespace App\Http\Middleware;

use App\Models\BannedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class BlockBannedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ipAddress = $request->ip();

        if ($ipAddress) {
            $normalizedIp = BannedIp::normalize($ipAddress);
            $rateLimitKey = BannedIp::limiterKey($normalizedIp);
            $ban = BannedIp::query()->active()->where('ip_address', $normalizedIp)->first();

            if ($ban) {
                if ($ban->expires_at === null) {
                    abort(403, 'Report submissions are not available from this IP address.');
                }

                if (! RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
                    RateLimiter::hit($rateLimitKey, max(1, now()->diffInSeconds($ban->expires_at)));
                }
                abort(403, 'Report submissions are not available from this IP address.');
            }

            RateLimiter::clear($rateLimitKey);
        }

        return $next($request);
    }
}