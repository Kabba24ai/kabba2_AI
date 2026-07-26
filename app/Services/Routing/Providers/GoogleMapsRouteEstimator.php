<?php

namespace App\Services\Routing\Providers;

use App\Enums\Routing\RouteStatus;
use App\Enums\Routing\TravelMode;
use App\Services\Routing\Contracts\Geocoder;
use App\Services\Routing\Contracts\RouteEstimator;
use App\Services\Routing\Contracts\RoutingConfigProvider;
use App\Services\Routing\GeoPoint;
use App\Services\Routing\RouteEstimateRequest;
use App\Services\Routing\RouteEstimateResult;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Google Routes API (Compute Routes v2) implementation of the shared
 * RouteEstimator. All calls are server-side. Addresses without coordinates are
 * resolved through the shared Geocoder (centralized + cached), so this provider
 * always works from lat/lng waypoints and can snapshot both normalized address
 * and coordinates.
 *
 * Uses the modern Routes API — NOT the legacy Distance Matrix API — per the
 * platform routing standard.
 */
class GoogleMapsRouteEstimator implements RouteEstimator
{
    private const ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';
    private const PROVIDER = 'google';

    public function __construct(
        private readonly RoutingConfigProvider $config,
        private readonly Geocoder $geocoder,
    ) {
    }

    public function estimate(RouteEstimateRequest $request): RouteEstimateResult
    {
        $key = $this->config->apiKey();
        if ($key === null || $key === '') {
            return RouteEstimateResult::failed(self::PROVIDER, RouteStatus::NotConfigured);
        }

        // Resolve both endpoints to coordinates (+ normalized address) first, so
        // input errors surface as InvalidOrigin/InvalidDestination/Geocoding
        // before any routing call is made.
        [$originPoint, $originLabel, $originError] = $this->resolveEndpoint(
            $request->originPoint, $request->originAddress, RouteStatus::InvalidOrigin
        );
        if ($originError !== null) {
            return RouteEstimateResult::failed(self::PROVIDER, $originError);
        }

        [$destPoint, $destLabel, $destError] = $this->resolveEndpoint(
            $request->destinationPoint, $request->destinationAddress, RouteStatus::InvalidDestination
        );
        if ($destError !== null) {
            return RouteEstimateResult::failed(self::PROVIDER, $destError);
        }

        // Traffic-aware routing requires a future departure time. Fall back to a
        // static estimate when traffic is off or the departure time is not in
        // the future (Google rejects a past departureTime for traffic-aware).
        $departure   = $request->departureAt ? CarbonImmutable::instance($request->departureAt) : null;
        $trafficMode = $request->trafficAware && $departure !== null && $departure->isFuture();

        $payload = [
            'origin'      => $this->waypoint($originPoint),
            'destination' => $this->waypoint($destPoint),
            'travelMode'  => $this->mapTravelMode($request->travelMode),
            'routingPreference' => $trafficMode ? 'TRAFFIC_AWARE' : 'TRAFFIC_UNAWARE',
            'units'       => strtoupper($this->config->units()) === 'METRIC' ? 'METRIC' : 'IMPERIAL',
        ];
        if ($trafficMode) {
            $payload['departureTime'] = $departure->toIso8601ZuluString();
        }

        try {
            $response = Http::connectTimeout($this->config->timeout())
                ->timeout($this->config->timeout())
                ->withHeaders([
                    'X-Goog-Api-Key'   => $key,
                    'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration,routes.staticDuration',
                ])
                ->acceptJson()
                ->post(self::ENDPOINT, $payload);
        } catch (ConnectionException $e) {
            return RouteEstimateResult::failed(self::PROVIDER, RouteStatus::Timeout, 'Routing connection failed.');
        } catch (Throwable $e) {
            return RouteEstimateResult::failed(self::PROVIDER, RouteStatus::ProviderUnavailable, 'Routing request failed.');
        }

        if ($response->failed()) {
            return RouteEstimateResult::failed(self::PROVIDER, $this->mapHttpError($response->status()),
                $response->json('error.message') ?? ('HTTP ' . $response->status()));
        }

        $route = $response->json('routes.0');
        if (!$route || !isset($route['distanceMeters'])) {
            return RouteEstimateResult::failed(self::PROVIDER, RouteStatus::NoRoute, 'No route returned.');
        }

        $baseSeconds    = $this->parseDuration($route['staticDuration'] ?? $route['duration'] ?? null);
        $trafficSeconds = $trafficMode ? $this->parseDuration($route['duration'] ?? null) : null;
        $effective      = $trafficSeconds ?? $baseSeconds;

        $arrival = ($departure !== null && $effective !== null)
            ? $departure->addSeconds($effective)
            : null;

        return new RouteEstimateResult(
            status: RouteStatus::Ok,
            provider: self::PROVIDER,
            normalizedOrigin: $originLabel,
            normalizedDestination: $destLabel,
            originPoint: $originPoint,
            destinationPoint: $destPoint,
            distanceMeters: (int) $route['distanceMeters'],
            durationSeconds: $baseSeconds,
            trafficDurationSeconds: ($trafficSeconds !== null && $trafficSeconds !== $baseSeconds) ? $trafficSeconds : null,
            expectedArrival: $arrival,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * @return array{0: ?GeoPoint, 1: ?string, 2: ?RouteStatus}  [point, normalizedLabel, error]
     */
    private function resolveEndpoint(?GeoPoint $point, ?string $address, RouteStatus $invalidStatus): array
    {
        if ($point !== null) {
            return [$point, $address !== null && trim($address) !== '' ? trim($address) : null, null];
        }

        if ($address === null || trim($address) === '') {
            return [null, null, $invalidStatus];
        }

        $geocoded = $this->geocoder->geocode($address);
        if (!$geocoded->isOk()) {
            return [null, null, $geocoded->status];
        }

        return [$geocoded->point, $geocoded->normalizedAddress, null];
    }

    private function waypoint(GeoPoint $point): array
    {
        return ['location' => ['latLng' => [
            'latitude'  => $point->latitude,
            'longitude' => $point->longitude,
        ]]];
    }

    private function mapTravelMode(TravelMode $mode): string
    {
        return match ($mode) {
            TravelMode::Driving => 'DRIVE',
        };
    }

    /** Google durations are RFC3339 second strings like "1234s". */
    private function parseDuration(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) rtrim($value, 's');
    }

    private function mapHttpError(int $status): RouteStatus
    {
        return match (true) {
            $status === 401 || $status === 403 => RouteStatus::InvalidCredentials,
            $status === 429 => RouteStatus::RateLimit,
            $status >= 500  => RouteStatus::ProviderUnavailable,
            default         => RouteStatus::NoRoute,
        };
    }
}
