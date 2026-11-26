<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminRoutingController extends Controller
{
    public function route(Request $request): JsonResponse
    {
        $coordinates = $request->validate([
            'from_lat' => ['required', 'numeric', 'between:-90,90'],
            'from_lng' => ['required', 'numeric', 'between:-180,180'],
            'to_lat' => ['required', 'numeric', 'between:-90,90'],
            'to_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $apiKey = (string) config('services.openrouteservice.key');
        abort_if($apiKey === '', 503, 'Routing is not configured.');

        $cacheCoordinates = array_map(static fn ($value): string => number_format((float) $value, 5, '.', ''), $coordinates);
        $cacheKey = 'live-map:route:'.hash('sha256', implode(',', $cacheCoordinates));
        $cachedRoute = rescue(fn () => Cache::get($cacheKey));

        if (is_array($cachedRoute)) {
            if (($cachedRoute['unavailable'] ?? false) === true) {
                return response()->json(['message' => 'Routing is temporarily unavailable.'], 502)
                    ->header('Cache-Control', 'private, no-store');
            }

            return response()->json($cachedRoute)->header('Cache-Control', 'private, no-store');
        }

        $providerStatus = null;
        $providerErrorCode = null;

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8',
            ])
                ->withHeader('Authorization', $apiKey)
                ->connectTimeout(2)
                ->timeout(5)
                ->get(config('services.openrouteservice.directions_url'), [
                    'start' => $coordinates['from_lng'].','.$coordinates['from_lat'],
                    'end' => $coordinates['to_lng'].','.$coordinates['to_lat'],
                ]);

            if (! $response->successful()) {
                $providerStatus = $response->status();
                $providerErrorCode = data_get($response->json(), 'error.code');
                throw new \RuntimeException('The routing provider returned HTTP '.$response->status().'.');
            }

            $feature = $response->json('features.0');
            $distance = data_get($feature, 'properties.segments.0.distance');

            if (! is_array($feature) || ! is_array(data_get($feature, 'geometry.coordinates')) || ! is_numeric($distance)) {
                throw new \RuntimeException('The routing provider returned an invalid route.');
            }

            $route = [
                'features' => [[
                    'geometry' => ['coordinates' => $feature['geometry']['coordinates']],
                    'properties' => ['segments' => [['distance' => (float) $distance]]],
                ]],
            ];
            rescue(fn () => Cache::put($cacheKey, $route, now()->addHour()));

            return response()->json($route)->header('Cache-Control', 'private, no-store');
        } catch (Throwable $exception) {
            rescue(fn () => Cache::put($cacheKey, ['unavailable' => true], now()->addSeconds(3)));
            Log::warning('Live map route lookup failed.', [
                'exception' => $exception::class,
                'provider_status' => $providerStatus,
                'provider_error_code' => $providerErrorCode,
            ]);

            return response()->json(['message' => 'Routing is temporarily unavailable.'], 502)
                ->header('Cache-Control', 'private, no-store');
        }
    }
}
