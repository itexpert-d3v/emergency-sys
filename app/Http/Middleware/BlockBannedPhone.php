<?php

namespace App\Http\Middleware;

use App\Models\BannedPhone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockBannedPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        $phoneNumber = $request->input('contact_number');

        if (is_string($phoneNumber) && $phoneNumber !== '') {
            $normalized = BannedPhone::normalize($phoneNumber);
            if ($normalized !== '' && BannedPhone::query()->where('phone_number', $normalized)->exists()) {
                abort(403, 'Report submissions are not available for this contact number.');
            }
        }

        return $next($request);
    }
}