<?php

namespace App\Services\Routing;

use App\Enums\Routing\RouteStatus;

/**
 * The immutable outcome of geocoding one address. Shared by every consumer;
 * a non-Ok status carries no coordinates.
 */
final class GeocodeResult
{
    public function __construct(
        public readonly RouteStatus $status,
        public readonly ?string $normalizedAddress = null,
        public readonly ?GeoPoint $point = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $errorDetail = null,
    ) {
    }

    public static function ok(string $normalizedAddress, GeoPoint $point, ?string $providerReference = null): self
    {
        return new self(RouteStatus::Ok, $normalizedAddress, $point, $providerReference);
    }

    public static function failed(RouteStatus $status, ?string $errorDetail = null): self
    {
        return new self($status, errorDetail: $errorDetail);
    }

    public function isOk(): bool
    {
        return $this->status->isOk() && $this->point !== null;
    }
}
