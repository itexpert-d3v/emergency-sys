<?php

namespace App\Services\Security;

use App\Models\BannedIp;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class ProgressiveRateLimitState
{
    public function cache(): Repository
    {
        return Cache::store(config('progressive_rate_limit.cache_store'));
    }

    public function consumeWindow(string $key, int $seconds, int $limit, float $timestamp, string $ipAddress): array
    {
        $cache = $this->cache();
        $windowKey = 'progressive-window:'.hash('sha256', $key);

        $result = $cache->lock($windowKey.':lock', 5)->block(2, function () use ($cache, $windowKey, $seconds, $limit, $timestamp): array {
            $cutoff = $timestamp - $seconds;
            $timestamps = array_values(array_filter(
                $cache->get($windowKey, []),
                static fn (mixed $hit): bool => is_numeric($hit) && (float) $hit > $cutoff,
            ));
            $timestamps[] = $timestamp;
            $cache->put($windowKey, $timestamps, $seconds);
            $count = count($timestamps);

            if ($count <= $limit) {
                return ['count' => $count, 'retry_after' => 0];
            }

            $releaseIndex = min($count - 1, max(0, $count - $limit));
            $retryAfter = max(1, (int) ceil($timestamps[$releaseIndex] + $seconds - $timestamp));

            return ['count' => $count, 'retry_after' => $retryAfter];
        });

        $this->registerIpKey($ipAddress, $windowKey);

        return $result;
    }

    public function incrementViolations(string $ipAddress): int
    {
        $cache = $this->cache();
        $key = $this->violationKey($ipAddress);

        return $cache->lock($key.':lock', 5)->block(2, function () use ($cache, $key): int {
            $count = (int) $cache->get($key, 0) + 1;
            $cache->put($key, $count, (int) config('progressive_rate_limit.violation_ttl_seconds', 86400));

            return $count;
        });
    }

    public function temporaryBanRemaining(string $ipAddress): int
    {
        $until = $this->cache()->get($this->temporaryBanKey($ipAddress));

        return is_numeric($until) ? max(0, (int) $until - now()->timestamp) : 0;
    }

    public function setTemporaryBan(string $ipAddress, int $seconds): int
    {
        $until = now()->timestamp + $seconds;
        $this->cache()->put($this->temporaryBanKey($ipAddress), $until, $seconds);

        return $until;
    }

    public function permanentBanCached(string $ipAddress): bool
    {
        $cache = $this->cache();
        $key = BannedIp::progressiveBanKey($ipAddress);

        if ($cache->get($key) === true) {
            return true;
        }

        $cached = $cache->get('progressive-ban-check:'.hash('sha256', $ipAddress));
        if (is_bool($cached)) {
            return $cached;
        }

        $ban = BannedIp::query()
            ->where('ip_address', $ipAddress)
            ->where('violation_count', '>=', (int) config('progressive_rate_limit.permanent_violation_count', 5))
            ->whereNull('expires_at')
            ->first(['expires_at']);
        $isBanned = $ban !== null;

        if ($isBanned) {
            $cache->forever($key, true);
        } else {
            $cache->put('progressive-ban-check:'.hash('sha256', $ipAddress), false, 60);
        }

        return $isBanned;
    }

    public function cachePermanentBan(string $ipAddress): void
    {
        $this->cache()->forever(BannedIp::progressiveBanKey($ipAddress), true);
        $this->cache()->put('progressive-ban-check:'.hash('sha256', $ipAddress), true, 60);
    }

    public function cachedGeolocation(string $ipAddress): ?array
    {
        $value = $this->cache()->get($this->geoKey($ipAddress));

        return is_array($value) ? $value : null;
    }

    public function cacheGeolocation(string $ipAddress, array $details): void
    {
        $this->cache()->put(
            $this->geoKey($ipAddress),
            $details,
            (int) config('progressive_rate_limit.geoip.lookup_cache_seconds', 2592000),
        );
    }

    public function clearIp(string $ipAddress): void
    {
        $ipAddress = BannedIp::normalize($ipAddress);
        $cache = $this->cache();
        $registryKey = $this->registryKey($ipAddress);

        foreach ($cache->get($registryKey, []) as $key) {
            $cache->forget($key);
        }

        $cache->forget($registryKey);
        $cache->forget($this->violationKey($ipAddress));
        $cache->forget($this->temporaryBanKey($ipAddress));
        $cache->forget($this->geoKey($ipAddress));
        $cache->forget(BannedIp::progressiveBanKey($ipAddress));
        $cache->forget('progressive-ban-check:'.hash('sha256', $ipAddress));
        Cache::forget(BannedIp::limiterKey($ipAddress));
    }

    public function violationKey(string $ipAddress): string
    {
        return 'progressive-violations:'.hash('sha256', $ipAddress);
    }

    public function registerIpKey(string $ipAddress, string $key): void
    {
        $cache = $this->cache();
        $registryKey = $this->registryKey($ipAddress);
        $keys = $cache->get($registryKey, []);
        $keys[] = $key;
        $cache->put($registryKey, array_values(array_unique($keys)), (int) config('progressive_rate_limit.violation_ttl_seconds', 86400));
    }

    private function temporaryBanKey(string $ipAddress): string
    {
        return 'progressive-temporary-ban:'.hash('sha256', $ipAddress);
    }

    private function geoKey(string $ipAddress): string
    {
        return 'progressive-geo:'.hash('sha256', $ipAddress);
    }

    private function registryKey(string $ipAddress): string
    {
        return 'progressive-ip-keys:'.hash('sha256', $ipAddress);
    }
}
