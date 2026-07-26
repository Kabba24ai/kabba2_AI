<?php

namespace App\Services\Routing\Providers;

use App\Enums\Routing\RouteStatus;
use App\Services\Routing\Contracts\Geocoder;
use App\Services\Routing\Contracts\RoutingConfigProvider;
use App\Services\Routing\GeocodeResult;
use App\Services\Routing\GeoPoint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Geocoding API implementation of the shared Geocoder. All calls are
 * server-side (browser → Kabba → Google); the API key never reaches the client.
 *
 * Successful lookups are cached (addresses rarely move) so an unchanged address
 * is never re-geocoded — the centralized cache Dispatch will also benefit from.
 * Failures are never cached (a transient outage must not poison the address).
 */
class GoogleMapsGeocoder implements Geocoder
{
    private const ENDPOINT   = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const CACHE_TTL  = 60 * 60 * 24 * 30; // 30 days
    private const CACHE_PREFIX = 'routing:geocode:google:';

    public function __construct(private readonly RoutingConfigProvider $config)
    {
    }

    public function geocode(string $address): GeocodeResult
    {
        $address = trim($address);
        if ($address === '') {
            return GeocodeResult::failed(RouteStatus::GeocodingFailure, 'Empty address.');
        }

        $key = $this->config->apiKey();
        if ($key === null || $key === '') {
            return GeocodeResult::failed(RouteStatus::NotConfigured);
        }

        $cacheKey = self::CACHE_PREFIX . sha1(mb_strtolower($address));
        if ($cached = Cache::get($cacheKey)) {
            return GeocodeResult::ok(
                $cached['normalized_address'],
                new GeoPoint($cached['latitude'], $cached['longitude']),
                $cached['reference'] ?? null,
            );
        }

        try {
            $response = Http::connectTimeout($this->config->timeout())
                ->timeout($this->config->timeout())
                ->acceptJson()
                ->get(self::ENDPOINT, ['address' => $address, 'key' => $key]);
        } catch (ConnectionException $e) {
            return GeocodeResult::failed(RouteStatus::Timeout, 'Geocoding connection failed.');
        } catch (Throwable $e) {
            return GeocodeResult::failed(RouteStatus::ProviderUnavailable, 'Geocoding request failed.');
        }

        if ($response->serverError()) {
            return GeocodeResult::failed(RouteStatus::ProviderUnavailable, 'Geocoding provider returned a server error.');
        }

        $body   = $response->json();
        $status = $body['status'] ?? 'UNKNOWN_ERROR';

        if ($status !== 'OK' || empty($body['results'][0])) {
            return GeocodeResult::failed($this->mapStatus($status), $body['error_message'] ?? $status);
        }

        $result   = $body['results'][0];
        $location = $result['geometry']['location'] ?? null;
        if (!isset($location['lat'], $location['lng'])) {
            return GeocodeResult::failed(RouteStatus::GeocodingFailure, 'Geocoding response missing coordinates.');
        }

        $normalized = $result['formatted_address'] ?? $address;
        $point      = new GeoPoint((float) $location['lat'], (float) $location['lng']);
        $reference  = $result['place_id'] ?? null;

        Cache::put($cacheKey, [
            'normalized_address' => $normalized,
            'latitude'           => $point->latitude,
            'longitude'          => $point->longitude,
            'reference'          => $reference,
        ], self::CACHE_TTL);

        return GeocodeResult::ok($normalized, $point, $reference);
    }

    private function mapStatus(string $googleStatus): RouteStatus
    {
        return match ($googleStatus) {
            'ZERO_RESULTS', 'INVALID_REQUEST' => RouteStatus::GeocodingFailure,
            'OVER_QUERY_LIMIT', 'OVER_DAILY_LIMIT' => RouteStatus::RateLimit,
            'REQUEST_DENIED' => RouteStatus::InvalidCredentials,
            default => RouteStatus::ProviderUnavailable,
        };
    }
}
