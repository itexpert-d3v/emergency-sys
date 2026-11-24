<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivateAuthenticatedResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
