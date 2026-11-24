# Progressive rate limiting operations

The progressive guard wraps routes that already have a Laravel `throttle` middleware. It runs before Laravel's native throttle and uses the named profile from `config/progressive_rate_limit.php`, preserving the existing per-route thresholds while adding a sliding timestamp window and escalating IP cooldowns.

## Environment switch

`PROGRESSIVE_RATE_LIMITING` accepts `true` or `false`. When left blank, the config defaults are:

| `APP_ENV` | Progressive guard and named Laravel throttles |
|---|---|
| `local`, `development` | Disabled |
| `testing`, `production`, other environments | Enabled |

Use `PROGRESSIVE_RATE_LIMITING=true` to test protection locally, or `false` for a controlled troubleshooting window. Do not disable protections in production without an approved incident/change record. After changing environment values, rebuild Laravel's config cache (`php artisan config:cache`) or clear it (`php artisan config:clear`) as appropriate.

## Defaults

- Sliding windows retain the named profile limits in `config/progressive_rate_limit.php`.
- Violation count decays after 24 hours without a new violation.
- Temporary bans escalate through 60 seconds, 5 minutes, 30 minutes, and 2 hours.
- The fifth violation in the decay period creates a permanent IP ban. A 100-request burst in 10 seconds also creates a permanent ban.
- Active temporary/permanent bans are cached; counters use atomic cache locks. Use a shared lock-capable cache in a multi-instance deployment. Redis is recommended for high-throughput production; the project default remains database cache unless `RATE_LIMIT_CACHE_STORE` is set.
- Existing human-issued IP bans continue to expire after 24 hours and are enforced on report submissions, preserving the current moderation workflow. Progressive permanent bans apply across profiled routes.

## GeoIP and regional policy

GeoIP lookups happen only after a rate-limit violation or severe burst. Provide local MaxMind GeoLite2 City and ASN `.mmdb` files and set `GEOIP_CITY_DATABASE` / `GEOIP_ASN_DATABASE` to their paths. The application does not download databases or call a third-party GeoIP service. Missing files and lookup errors do not block ordinary requests; location fields remain empty.

`RATE_LIMIT_PRIMARY_COUNTRY` defaults to `PH`; cached traffic from another country uses half the configured request threshold. `RATE_LIMIT_COUNTRY_ALLOWLIST` and `RATE_LIMIT_COUNTRY_BLOCKLIST` accept comma-separated ISO country codes. Regional rules apply only after the address has been looked up and cached by a violation, preserving the no-lookup-on-normal-traffic requirement. Verify country policy and privacy/legal approval before enabling it.

## Unban an IP

Use the admin moderation page, or run the console command with an explicit IPv4/IPv6 address:

```sh
php artisan ip:unban 198.51.100.25
```

The command removes the database ban and clears progressive counters, sliding windows, temporary/permanent ban cache, and cached GeoIP data for that address. IP violation history is retained for audit/operations review and should follow the approved retention schedule.

## Database grants

After applying the progressive ban migration, provision the application account with the new grants in [the MySQL bootstrap runbook](bootstrap-mysql-user.md): update access to `banned_ips`, plus append/read access to `rate_limit_violations`. Do not grant UPDATE/DELETE on the violation-history table. Keep the migration account separate from the runtime account.

## Verification

Run the focused middleware and compatibility tests:

```sh
php artisan test --filter=ProgressiveRateLimitTest
php artisan test --filter='PublicReportingTest|StaffDashboardTest|test_login_attempts_are_limited_per_ip_and_normalized_email'
```

Test production-default config and configured cache locks in staging. Local tests use the array cache; the production cache store must support atomic locks and shared state across application instances.
