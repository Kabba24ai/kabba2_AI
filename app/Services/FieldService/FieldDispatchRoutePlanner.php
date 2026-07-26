<?php

namespace App\Services\FieldService;

use App\Enums\Routing\RouteStatus;
use App\Models\Stores\Store;
use App\Services\Routing\Contracts\RoutingConfigProvider;
use App\Services\Routing\GeoPoint;
use App\Services\Routing\RouteEstimateRequest;
use App\Services\Routing\RouteEstimateResult;
use App\Services\Routing\RoutingService;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Field Service's consumer adapter over the shared routing layer. It resolves a
 * dispatcher's explicit departure choice (a store, or an "Other" structured
 * address) and the resolved destination into a provider-neutral
 * RouteEstimateRequest, then delegates to the shared RoutingService.
 *
 * Deliberately thin and Field-specific: the provider integration and contracts
 * live in App\Services\Routing and are shared; Dispatch will write its own
 * analogous adapter over the SAME RoutingService without touching this class.
 *
 * The store origin is always resolved from the DB server-side (never from
 * client-supplied coordinates) so a tampered or stale browser value can't drive
 * the calculation.
 */
class FieldDispatchRoutePlanner
{
    public function __construct(
        private readonly RoutingService $routing,
        private readonly RoutingConfigProvider $config,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->routing->isConfigured();
    }

    /**
     * @param array<string,mixed> $data keys: departure_location_type,
     *   departure_store_id, departure_street, departure_line2, departure_city,
     *   departure_state, departure_zip, destination_address, departure_at
     */
    public function estimate(array $data): RouteEstimateResult
    {
        [$originAddress, $originPoint, $originError] = $this->resolveOrigin($data);
        if ($originError !== null) {
            return RouteEstimateResult::failed($this->config->provider(), $originError);
        }

        $destination = trim((string) ($data['destination_address'] ?? ''));
        if ($destination === '') {
            return RouteEstimateResult::failed($this->config->provider(), RouteStatus::InvalidDestination);
        }

        $departureAt = null;
        if (!empty($data['departure_at'])) {
            try {
                $departureAt = CarbonImmutable::parse($data['departure_at']);
            } catch (Throwable $e) {
                $departureAt = null;
            }
        }

        return $this->routing->estimate(new RouteEstimateRequest(
            originAddress: $originAddress,
            originPoint: $originPoint,
            destinationAddress: $destination,
            departureAt: $departureAt,
            trafficAware: $this->config->trafficAware(),
            consumer: 'field_service',
        ));
    }

    /**
     * @return array{0: ?string, 1: ?GeoPoint, 2: ?RouteStatus}  [address, point, error]
     */
    private function resolveOrigin(array $data): array
    {
        $type = $data['departure_location_type'] ?? null;

        if ($type === 'store') {
            $store = Store::find($data['departure_store_id'] ?? null);
            if (!$store) {
                return [null, null, RouteStatus::InvalidOrigin];
            }

            // Canonical store address + stored coordinates when valid.
            return [$store->full_address, GeoPoint::fromNullable($store->latitude, $store->longitude), null];
        }

        if ($type === 'other') {
            $address = self::assembleOtherAddress($data);

            return $address === '' ? [null, null, RouteStatus::InvalidOrigin] : [$address, null, null];
        }

        return [null, null, RouteStatus::InvalidOrigin];
    }

    /** Assemble the structured "Other" origin into a single geocodable line. */
    public static function assembleOtherAddress(array $data): string
    {
        return trim(implode(', ', array_filter([
            $data['departure_street'] ?? null,
            $data['departure_line2'] ?? null,
            $data['departure_city'] ?? null,
            trim(($data['departure_state'] ?? '') . ' ' . ($data['departure_zip'] ?? '')),
        ])));
    }
}
