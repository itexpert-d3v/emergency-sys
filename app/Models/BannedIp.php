<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BannedIp extends Model
{
    protected $table = 'banned_ips';

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public static function limiterKey(string $ipAddress): string
    {
        return 'temporary-ip-ban:' . hash('sha256', static::normalize($ipAddress));
    }

    public static function normalize(string $ipAddress): string
    {
        $packed = inet_pton($ipAddress);

        return $packed === false ? $ipAddress : (inet_ntop($packed) ?: $ipAddress);
    }
}