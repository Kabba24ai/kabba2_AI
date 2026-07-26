<?php

namespace App\Services\Routing;

/**
 * An immutable WGS-84 coordinate. Provider-neutral so both the geocoder and the
 * route estimator speak the same coordinate type regardless of provider.
 */
final class GeoPoint
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {
    }

    /** Build from loosely-typed store/DB values; null when either is missing/blank. */
    public static function fromNullable(mixed $latitude, mixed $longitude): ?self
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        return new self((float) $latitude, (float) $longitude);
    }

    public function toArray(): array
    {
        return ['latitude' => $this->latitude, 'longitude' => $this->longitude];
    }
}
