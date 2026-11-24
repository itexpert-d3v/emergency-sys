<?php

namespace App\Http\Middleware;

use App\Models\BannedIp;
use App\Models\RateLimitViolation;
use App\Services\Security\IpGeolocation;
use App\Services\Security\ProgressiveRateLimitState;
use Closure;
use Illuminate\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProgressiveRateLimit
{
    public function __construct(
        private readonly ProgressiveRateLimitState $state,
        private readonly IpGeolocation $geolocation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('progressive_rate_limit.enabled')) {
            return $next($request);
        }

        $profile = $this->profileFor($request);
        $ipAddress = $request->ip();

        if (! $profile || ! $ipAddress || filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return $next($request);
        }

        $ipAddress = BannedIp::normalize($ipAddress);

        try {
            if ($this->state->permanentBanCached($ipAddress)) {
                return $this->forbiddenResponse($request);
            }

            $severe = config('progressive_rate_limit.severe_burst');
            $burst = $this->state->consumeWindow(
                'severe:'.$ipAddress,
                (int) $severe['seconds'],
                (int) $severe['requests'] - 1,
                now()->valueOf() / 1000,
                $ipAddress,
            );

            if ($burst['count'] >= (int) $severe['requests']) {
                $this->recordViolation($request, $ipAddress, $profile, $burst['count'], (int) $severe['seconds'], true);

                return $this->forbiddenResponse($request);
            }

            $geo = $this->state->cachedGeolocation($ipAddress);
            if ($geo && $this->countryIsRestricted($geo['country_code'] ?? null)) {
                return $this->forbiddenResponse($request);
            }

            $temporaryBanRemaining = $this->state->temporaryBanRemaining($ipAddress);
            if ($temporaryBanRemaining > 0) {
                return $this->rateLimitResponse($request, $temporaryBanRemaining, $this->profileLimit($profile));
            }

            $multiplier = $this->rateMultiplier($geo['country_code'] ?? null);
            $violations = [];
            foreach ($this->limitsFor($profile) as $index => $window) {
                $requests = max(1, (int) floor((int) $window['requests'] * $multiplier));
                foreach ($this->dimensionsFor($request, $window) as $dimension => $identifier) {
                    $key = implode(':', [
                        $profile,
                        $index,
                        $dimension,
                        hash('sha256', (string) $identifier),
                        $requests,
                        (int) $window['seconds'],
                    ]);
                    $hit = $this->state->consumeWindow(
                        $key,
                        (int) $window['seconds'],
                        $requests,
                        now()->valueOf() / 1000,
                        $ipAddress,
                    );

                    if ($hit['count'] > $requests) {
                        $violations[] = [
                            'count' => $hit['count'],
                            'limit' => $requests,
                            'seconds' => (int) $window['seconds'],
                            'retry_after' => (int) $hit['retry_after'],
                        ];
                    }
                }
            }

            if ($violations === []) {
                return $next($request);
            }

            usort($violations, static fn (array $left, array $right): int => $right['retry_after'] <=> $left['retry_after']);
            $violation = $violations[0];
            [$count, $permanent, $banUntil] = $this->recordViolation(
                $request,
                $ipAddress,
                $profile,
                $violation['count'],
                $violation['seconds'],
                false,
            );

            if ($permanent) {
                return $this->forbiddenResponse($request);
            }

            $tiers = config('progressive_rate_limit.temporary_bans', [60, 300, 1800, 7200]);
            $temporarySeconds = (int) $tiers[min($count - 1, count($tiers) - 1)];

            return $this->rateLimitResponse($request, $temporarySeconds, $violation['limit'], $banUntil);
        } catch (LockTimeoutException) {
            return response('Rate protection is temporarily unavailable. Please retry shortly.', 503)
                ->header('Retry-After', '1');
        } catch (Throwable) {
            return response('Rate protection is temporarily unavailable. Please retry shortly.', 503)
                ->header('Retry-After', '1');
        }
    }

    private function profileFor(Request $request): ?string
    {
        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (! str_contains(strtolower($middleware), 'throttlerequests') && ! str_starts_with($middleware, 'throttle:')) {
                continue;
            }

            $argument = explode(':', $middleware, 2)[1] ?? '';
            if ($argument === '') {
                return 'default';
            }

            if (isset(config('progressive_rate_limit.profiles')[$argument])) {
                return $argument;
            }

            if (preg_match('/^(\d+),(\d+)$/', $argument, $matches)) {
                $profile = 'inline-'.$matches[1].'-'.$matches[2];
                config(['progressive_rate_limit.profiles.'.$profile => [[
                    'requests' => (int) $matches[1],
                    'seconds' => max(1, (int) $matches[2] * 60),
                ]]]);

                return $profile;
            }
        }

        return null;
    }

    private function limitsFor(string $profile): array
    {
        return config('progressive_rate_limit.profiles.'.$profile)
            ?? config('progressive_rate_limit.profiles.default', [['requests' => 30, 'seconds' => 60]]);
    }

    private function profileLimit(string $profile): int
    {
        return (int) ($this->limitsFor($profile)[0]['requests'] ?? 30);
    }

    private function dimensionsFor(Request $request, array $window): array
    {
        $dimension = $window['dimension'] ?? null;

        if ($dimension === 'token') {
            $token = (string) $request->route('token', '');

            return $token === '' ? [] : ['token' => $token];
        }

        if ($dimension === 'ip') {
            return ['ip' => (string) $request->ip()];
        }

        if ($dimension === 'user') {
            return $request->user() ? ['user' => (string) $request->user()->getAuthIdentifier()] : [];
        }

        $dimensions = ['ip' => (string) $request->ip()];
        if ($request->user()) {
            $dimensions['user'] = (string) $request->user()->getAuthIdentifier();
        }

        return $dimensions;
    }

    private function recordViolation(
        Request $request,
        string $ipAddress,
        string $profile,
        int $requestCount,
        int $windowSeconds,
        bool $severe,
    ): array {
        $count = $this->state->incrementViolations($ipAddress);
        $geo = $this->state->cachedGeolocation($ipAddress);
        if ($geo === null) {
            $geo = $this->geolocation->lookup($ipAddress);
            $this->state->cacheGeolocation($ipAddress, $geo);
        }

        $permanent = $severe || $count >= (int) config('progressive_rate_limit.permanent_violation_count', 5);
        $banUntil = null;
        if ($permanent) {
            BannedIp::query()->updateOrCreate(
                ['ip_address' => $ipAddress],
                [
                    'reason' => $severe ? 'Severe rate-limit burst' : 'Progressive rate-limit escalation',
                    'violation_count' => max($count, (int) config('progressive_rate_limit.permanent_violation_count', 5)),
                    'country_code' => $geo['country_code'] ?? null,
                    'region' => $geo['region'] ?? null,
                    'city' => $geo['city'] ?? null,
                    'asn' => $geo['asn'] ?? null,
                    'isp' => $geo['isp'] ?? null,
                    'banned_at' => now(),
                    'expires_at' => null,
                ],
            );
            $this->state->cachePermanentBan($ipAddress);
        } else {
            $tiers = config('progressive_rate_limit.temporary_bans', [60, 300, 1800, 7200]);
            $seconds = (int) $tiers[min($count - 1, count($tiers) - 1)];
            $banUntil = $this->state->setTemporaryBan($ipAddress, $seconds);
        }

        RateLimitViolation::query()->create([
            'ip_address' => $ipAddress,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'profile' => $profile,
            'violation_count' => $count,
            'request_count' => $requestCount,
            'window_seconds' => $windowSeconds,
            'is_permanent' => $permanent,
            'banned_until' => $banUntil ? now()->addSeconds(max(0, $banUntil - now()->timestamp)) : null,
            'country_code' => $geo['country_code'] ?? null,
            'region' => $geo['region'] ?? null,
            'city' => $geo['city'] ?? null,
            'asn' => $geo['asn'] ?? null,
            'isp' => $geo['isp'] ?? null,
        ]);

        return [$count, $permanent, $banUntil];
    }

    private function countryIsRestricted(?string $country): bool
    {
        if ($country === null || $country === '') {
            return false;
        }

        $country = strtoupper($country);
        $allowlist = config('progressive_rate_limit.geoip.country_allowlist', []);
        $blocklist = config('progressive_rate_limit.geoip.country_blocklist', []);

        return in_array($country, $blocklist, true)
            || ($allowlist !== [] && ! in_array($country, $allowlist, true));
    }

    private function rateMultiplier(?string $country): float
    {
        return $country !== null
            && strtoupper($country) !== config('progressive_rate_limit.geoip.primary_country', 'PH')
                ? (float) config('progressive_rate_limit.geoip.outside_primary_multiplier', 0.5)
                : 1.0;
    }

    private function rateLimitResponse(Request $request, int $retryAfter, int $limit, ?int $banUntil = null): Response
    {
        $retryAfter = max(1, $retryAfter);
        $message = 'Too many requests. Try again in '.$this->durationLabel($retryAfter).'.';
        $reset = $banUntil ?? (now()->timestamp + $retryAfter);
        $response = $request->expectsJson()
            ? response()->json(['message' => $message], 429)
            : response()->view('errors.429', ['message' => $message], 429);

        return $response
            ->header('Retry-After', (string) $retryAfter)
            ->header('X-RateLimit-Limit', (string) $limit)
            ->header('X-RateLimit-Remaining', '0')
            ->header('X-RateLimit-Reset', (string) $reset);
    }

    private function forbiddenResponse(Request $request): Response
    {
        return $request->expectsJson()
            ? response()->json(['message' => 'Access is unavailable from this network.'], 403)
            : response()->view('errors.403', [], 403);
    }

    private function durationLabel(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = (int) ceil($seconds / 3600);

            return $hours.' '.($hours === 1 ? 'hour' : 'hours');
        }

        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);

            return $minutes.' '.($minutes === 1 ? 'minute' : 'minutes');
        }

        return $seconds.' '.($seconds === 1 ? 'second' : 'seconds');
    }
}
