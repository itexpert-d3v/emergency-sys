<?php

namespace Tests\Feature;

use App\Models\BannedIp;
use App\Models\RateLimitViolation;
use App\Services\Security\ProgressiveRateLimitState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressiveRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'progressive_rate_limit.enabled' => true,
            'progressive_rate_limit.cache_store' => 'array',
            'progressive_rate_limit.profiles.auth' => [['requests' => 2, 'seconds' => 60]],
            'progressive_rate_limit.severe_burst' => ['requests' => 100, 'seconds' => 10],
            'progressive_rate_limit.geoip.city_database' => null,
            'progressive_rate_limit.geoip.asn_database' => null,
        ]);
    }

    public function test_guard_returns_retry_headers_and_records_a_violation_before_native_throttle(): void
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/login', [
                'email' => 'limited@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/login', [
            'email' => 'limited@example.test',
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Try again in 1 minute.')
            ->assertHeader('Retry-After', '60')
            ->assertHeader('X-RateLimit-Limit', '2')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertHeader('X-RateLimit-Reset');

        $this->assertDatabaseHas('rate_limit_violations', [
            'ip_address' => '127.0.0.1',
            'profile' => 'auth',
            'violation_count' => 1,
            'is_permanent' => false,
        ]);

        $this->assertSame(1, RateLimitViolation::query()->count());
    }

    public function test_html_lockout_response_includes_a_human_readable_wait_time(): void
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post('/login', [
                'email' => 'html-limited@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => 'html-limited@example.test',
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertSee('Please slow down')
            ->assertSee('Try again in 1 minute');
    }

    public function test_violations_escalate_to_the_second_temporary_ban_tier(): void
    {
        $this->exceedAuthLimit()->assertHeader('Retry-After', '60');

        $this->travel(61)->seconds();
        $this->exceedAuthLimit()
            ->assertJsonPath('message', 'Too many requests. Try again in 5 minutes.')
            ->assertHeader('Retry-After', '300');

        $this->assertDatabaseHas('rate_limit_violations', [
            'ip_address' => '127.0.0.1',
            'violation_count' => 2,
            'is_permanent' => false,
        ]);
    }

    public function test_fifth_violation_creates_a_permanent_ip_ban(): void
    {
        $this->exceedAuthLimit();
        foreach ([61, 301, 1801, 7201] as $seconds) {
            $this->travel($seconds)->seconds();
            $response = $this->exceedAuthLimit();
        }

        $response->assertForbidden();
        $this->assertDatabaseHas('banned_ips', [
            'ip_address' => '127.0.0.1',
            'reason' => 'Progressive rate-limit escalation',
            'violation_count' => 5,
            'expires_at' => null,
        ]);
        $this->assertDatabaseHas('rate_limit_violations', [
            'ip_address' => '127.0.0.1',
            'violation_count' => 5,
            'is_permanent' => true,
        ]);

        $this->postJson('/login', [
            'email' => 'limited@example.test',
            'password' => 'wrong-password',
        ])->assertForbidden();
    }

    public function test_severe_burst_creates_a_permanent_ip_ban(): void
    {
        config([
            'progressive_rate_limit.profiles.auth' => [['requests' => 100, 'seconds' => 60]],
            'progressive_rate_limit.severe_burst' => ['requests' => 3, 'seconds' => 10],
        ]);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/login', [
                'email' => 'burst@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/login', [
            'email' => 'burst@example.test',
            'password' => 'wrong-password',
        ])->assertForbidden();

        $this->assertDatabaseHas('banned_ips', [
            'ip_address' => '127.0.0.1',
            'reason' => 'Severe rate-limit burst',
            'expires_at' => null,
        ]);
    }

    public function test_cached_country_rules_and_outside_region_multiplier_are_applied(): void
    {
        $state = app(ProgressiveRateLimitState::class);
        $state->cacheGeolocation('127.0.0.1', ['country_code' => 'US']);
        config(['progressive_rate_limit.geoip.country_blocklist' => ['US']]);

        $this->get('/admin')->assertForbidden();

        $state->clearIp('127.0.0.1');
        config([
            'progressive_rate_limit.geoip.country_blocklist' => [],
            'progressive_rate_limit.profiles.auth' => [['requests' => 2, 'seconds' => 60]],
            'progressive_rate_limit.geoip.outside_primary_multiplier' => 0.5,
        ]);
        $state->cacheGeolocation('127.0.0.1', ['country_code' => 'US']);

        $this->postJson('/login', [
            'email' => 'regional@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $this->postJson('/login', [
            'email' => 'regional@example.test',
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Try again in 1 minute.')
            ->assertHeader('X-RateLimit-Limit', '1');
    }

    public function test_trusted_forwarded_ip_is_used_for_violation_and_ban_records(): void
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->withServerVariables([
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '198.51.100.55',
            ])->postJson('/login', [
                'email' => 'forwarded@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.55',
        ])->postJson('/login', [
            'email' => 'forwarded@example.test',
            'password' => 'wrong-password',
        ])->assertHeader('Retry-After', '60');

        $this->assertDatabaseHas('rate_limit_violations', [
            'ip_address' => '198.51.100.55',
            'profile' => 'auth',
        ]);
    }

    public function test_ip_unban_command_clears_all_progressive_cache_state(): void
    {
        $state = app(ProgressiveRateLimitState::class);
        $ipAddress = '198.51.100.77';
        BannedIp::query()->create([
            'ip_address' => $ipAddress,
            'expires_at' => null,
            'violation_count' => 5,
        ]);
        $state->incrementViolations($ipAddress);
        $state->setTemporaryBan($ipAddress, 60);
        $state->cacheGeolocation($ipAddress, ['country_code' => 'PH']);
        $state->cachePermanentBan($ipAddress);

        $this->artisan('ip:unban', ['ip' => $ipAddress])->assertExitCode(0);

        $this->assertDatabaseMissing('banned_ips', ['ip_address' => $ipAddress]);
        $this->assertSame(0, $state->temporaryBanRemaining($ipAddress));
        $this->assertNull($state->cachedGeolocation($ipAddress));
        $this->assertFalse($state->permanentBanCached($ipAddress));
        $this->assertSame(0, (int) $state->cache()->get($state->violationKey($ipAddress), 0));
    }

    public function test_permanent_progressive_ip_ban_is_enforced_on_all_profiled_routes(): void
    {
        BannedIp::query()->create([
            'ip_address' => '127.0.0.1',
            'reason' => 'Progressive rate-limit escalation',
            'violation_count' => 5,
            'expires_at' => null,
        ]);

        $this->get('/admin')->assertForbidden();
        $this->get('/api/categories')->assertForbidden();
    }

    private function exceedAuthLimit()
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $response = $this->postJson('/login', [
                'email' => 'limited@example.test',
                'password' => 'wrong-password',
            ]);
        }

        return $this->postJson('/login', [
            'email' => 'limited@example.test',
            'password' => 'wrong-password',
        ]);
    }
}
