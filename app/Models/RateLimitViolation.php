<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimitViolation extends Model
{
    protected $fillable = [
        'ip_address',
        'user_id',
        'profile',
        'violation_count',
        'request_count',
        'window_seconds',
        'is_permanent',
        'banned_until',
        'country_code',
        'region',
        'city',
        'asn',
        'isp',
    ];

    protected function casts(): array
    {
        return [
            'is_permanent' => 'boolean',
            'banned_until' => 'datetime',
        ];
    }
}
