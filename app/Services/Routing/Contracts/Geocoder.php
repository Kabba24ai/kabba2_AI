<?php

namespace App\Services\Routing\Contracts;

use App\Services\Routing\GeocodeResult;

/**
 * Resolves a free-form address to normalized coordinates. Provider-neutral and
 * shared across modules — callers never talk to a mapping provider directly.
 * Implementations must never throw for an unresolvable address or a provider
 * failure; they return a GeocodeResult carrying the normalized RouteStatus.
 */
interface Geocoder
{
    public function geocode(string $address): GeocodeResult;
}
