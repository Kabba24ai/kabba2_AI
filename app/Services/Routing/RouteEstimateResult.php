<?php

namespace App\Services\Routing;

use App\Enums\Routing\RouteStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The immutable outcome of one point-to-point route estimate — the shared
 * result contract for every consumer. Deliberately module-neutral: it exposes
 * normalized addresses, coordinates, distance/duration, an optional
 * traffic-aware duration, and a computed arrival, plus provenance (provider,
 * status, timestamp, reference). Consumers snapshot whatever subset their
 * domain needs; nothing here assumes Field Service columns.
 *
 * A non-Ok status carries no metrics — callers must branch on isOk() (or the
 * status) before reading distance/duration/arrival.
 */
final class RouteEstimateResult
{
    public function __construct(
        public readonly RouteStatus $status,
        public readonly string $provider,
        public readonly ?string $normalizedOrigin = null,
        public readonly ?string $normalizedDestination = null,
        public readonly ?GeoPoint $originPoint = null,
        public readonly ?GeoPoint $destinationPoint = null,
        public readonly ?int $distanceMeters = null,
        public readonly ?int $durationSeconds = null,
        public readonly ?int $trafficDurationSeconds = null,
        public readonly ?CarbonInterface $expectedArrival = null,
        public readonly ?CarbonInterface $calculatedAt = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $errorDetail = null,
    ) {
    }

    public static function failed(string $provider, RouteStatus $status, ?string $errorDetail = null): self
    {
        return new self($status, $provider, errorDetail: $errorDetail, calculatedAt: CarbonImmutable::now());
    }

    public function isOk(): bool
    {
        return $this->status->isOk();
    }

    /** Traffic-aware duration when the provider returned one, else the base duration. */
    public function effectiveDurationSeconds(): ?int
    {
        return $this->trafficDurationSeconds ?? $this->durationSeconds;
    }

    /** Human "42 min" / "1 hr 12 min" for the effective duration; null when unknown. */
    public function durationForHumans(): ?string
    {
        $seconds = $this->effectiveDurationSeconds();
        if ($seconds === null) {
            return null;
        }

        $minutes = (int) round($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $rem   = $minutes % 60;

        return $rem === 0 ? "{$hours} hr" : "{$hours} hr {$rem} min";
    }

    /**
     * The persistable snapshot a consumer stores alongside its own record so the
     * dispatch plan is preserved verbatim even if a store address later changes.
     */
    public function toSnapshot(): array
    {
        return [
            'status'                   => $this->status->value,
            'provider'                 => $this->provider,
            'normalized_origin'        => $this->normalizedOrigin,
            'normalized_destination'   => $this->normalizedDestination,
            'origin_latitude'          => $this->originPoint?->latitude,
            'origin_longitude'         => $this->originPoint?->longitude,
            'destination_latitude'     => $this->destinationPoint?->latitude,
            'destination_longitude'    => $this->destinationPoint?->longitude,
            'distance_meters'          => $this->distanceMeters,
            'duration_seconds'         => $this->durationSeconds,
            'traffic_duration_seconds' => $this->trafficDurationSeconds,
            'expected_arrival'         => $this->expectedArrival?->toIso8601String(),
            'calculated_at'            => $this->calculatedAt?->toIso8601String(),
            'provider_reference'       => $this->providerReference,
        ];
    }
}
