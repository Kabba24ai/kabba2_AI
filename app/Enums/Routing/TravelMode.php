<?php

namespace App\Enums\Routing;

/**
 * Travel mode for a route estimate. Provider-neutral; each provider maps these
 * to its own vocabulary. Only Driving is exercised today (Field Service truck /
 * technician). Future Dispatch may add heavy-vehicle modes — added here, mapped
 * in the provider, with no change to consumers.
 */
enum TravelMode: string
{
    case Driving = 'driving';

    public function label(): string
    {
        return match ($this) {
            self::Driving => 'Driving',
        };
    }
}
