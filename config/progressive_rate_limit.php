<?php

$environment = (string) env('APP_ENV', 'production');
$enabledByDefault = ! in_array($environment, ['local', 'development'], true);
$enabledOverride = env('PROGRESSIVE_RATE_LIMITING');
$enabled = $enabledOverride === null || $enabledOverride === ''
    ? $enabledByDefault
    : filter_var($enabledOverride, FILTER_VALIDATE_BOOLEAN);

return [
    'enabled' => $enabled,
    'cache_store' => env('RATE_LIMIT_CACHE_STORE', env('CACHE_STORE', env('APP_ENV') === 'production' ? 'redis' : 'database')),
    'violation_ttl_seconds' => 86400,
    'permanent_violation_count' => 5,
    'temporary_bans' => [60, 300, 1800, 7200],
    'severe_burst' => [
        'requests' => 100,
        'seconds' => 10,
    ],
    'profiles' => [
        'public-read' => [['requests' => 60, 'seconds' => 60]],
        'auth' => [['requests' => 5, 'seconds' => 60]],
        'public-report' => [
            ['requests' => 3, 'seconds' => 60],
            ['requests' => 60, 'seconds' => 3600],
        ],
        'public-tracking' => [['requests' => 10, 'seconds' => 60]],
        'public-message-read' => [['requests' => 20, 'seconds' => 60]],
        'public-message' => [
            ['requests' => 10, 'seconds' => 60],
            ['requests' => 8, 'seconds' => 60, 'dimension' => 'token'],
        ],
        'staff-read' => [['requests' => 60, 'seconds' => 60]],
        'live-map' => [['requests' => 120, 'seconds' => 60, 'dimension' => 'user']],
        'route-directions' => [['requests' => 30, 'seconds' => 60, 'dimension' => 'user']],
        'staff-write' => [['requests' => 30, 'seconds' => 60]],
        'admin-write' => [['requests' => 30, 'seconds' => 60]],
        'admin-sensitive' => [
            ['requests' => 5, 'seconds' => 60],
            ['requests' => 10, 'seconds' => 60, 'dimension' => 'ip'],
        ],
        'admin-reports' => [
            ['requests' => 5, 'seconds' => 60],
            ['requests' => 10, 'seconds' => 3600, 'dimension' => 'ip'],
        ],
        'news-read' => [['requests' => 30, 'seconds' => 60]],
        'default' => [['requests' => 30, 'seconds' => 60]],
    ],
    'geoip' => [
        'city_database' => env('GEOIP_CITY_DATABASE'),
        'asn_database' => env('GEOIP_ASN_DATABASE'),
        'lookup_cache_seconds' => 2592000,
        'primary_country' => strtoupper((string) env('RATE_LIMIT_PRIMARY_COUNTRY', 'PH')),
        'outside_primary_multiplier' => 0.5,
        'country_allowlist' => array_values(array_filter(array_map(
            static fn (string $country): string => strtoupper(trim($country)),
            explode(',', (string) env('RATE_LIMIT_COUNTRY_ALLOWLIST', '')),
        ))),
        'country_blocklist' => array_values(array_filter(array_map(
            static fn (string $country): string => strtoupper(trim($country)),
            explode(',', (string) env('RATE_LIMIT_COUNTRY_BLOCKLIST', '')),
        ))),
    ],
];
