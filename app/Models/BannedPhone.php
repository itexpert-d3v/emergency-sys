<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannedPhone extends Model
{
    protected $table = 'banned_phones';

    protected $guarded = [];

    public static function normalize(string $phoneNumber): string
    {
        return (string) preg_replace('/\D+/', '', $phoneNumber);
    }
}