<?php

namespace App\Services\Routing;

use App\Enums\Routing\TravelMode;
use Carbon\CarbonInterface;

/**
 * A single point-to-point route estimate request — the shared input contract
 * for every consumer of the routing layer. Deliberately module-neutral: it
 * carries an origin and a destination (each an address, coordinates, or both)
 * plus timing/mode preferences, with NO Field-Service-specific fields. Future
 * Dispatch builds many of these (one per leg) from its own domain objects.
 *
 * When coordinates are supplied they are used directly; otherwise the address
 * is geocoded through the shared Geocoder. Supplying both lets a caller pass a
 * known-good origin (e.g. a store's stored lat/lng) while keeping the human
 * address for display/snapshotting.
 */
final class RouteEstimateRequest
{
    public function __construct(
        public readonly ?string $originAddress = null,
        public readonly ?GeoPoint $originPoint = null,
        public readonly ?string $destinationAddress = null,
        public readonly ?GeoPoint $destinationPoint = null,
        public readonly ?CarbonInterface $departureAt = null,
        public readonly TravelMode $travelMode = TravelMode::Driving,
        public readonly bool $trafficAware = true,
        /** Free-form label for logging which consumer made the request (e.g. 'field_service'). */
        public readonly string $consumer = 'unknown',
    ) {
    }

    public function hasResolvableOrigin(): bool
    {
        return $this->originPoint !== null || $this->nonEmpty($this->originAddress);
    }

    public function hasResolvableDestination(): bool
    {
        return $this->destinationPoint !== null || $this->nonEmpty($this->destinationAddress);
    }

    private function nonEmpty(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
