<?php

namespace App\Services\Routing;

use App\Enums\Routing\RouteStatus;
use App\Models\Global\RouteRequestLog;
use App\Services\Routing\Contracts\Geocoder;
use App\Services\Routing\Contracts\RouteEstimator;
use App\Services\Routing\Contracts\RoutingConfigProvider;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The shared, platform-level entry point to routing. EVERY consumer goes
 * through this class — Field Service dispatch today, Dispatch route legs and
 * day-sequencing later — so the config gate, normalized failure handling, and
 * privacy-safe logging live in exactly one place.
 *
 * Guarantees:
 *  - Never throws. Provider/geocoding failures come back as a RouteStatus.
 *  - Never calls the provider when the integration is not configured.
 *  - Never logs API keys, auth headers, full customer addresses, or raw
 *    provider payloads (only module/provider/status/timing/place refs).
 */
class RoutingService
{
    public function __construct(
        private readonly RoutingConfigProvider $config,
        private readonly RouteEstimator $estimator,
        private readonly Geocoder $geocoder,
    ) {
    }

    /** Whether a live calculation can even be attempted. */
    public function isConfigured(): bool
    {
        return $this->config->isConfigured();
    }

    public function estimate(RouteEstimateRequest $request): RouteEstimateResult
    {
        if (!$this->config->isConfigured()) {
            return RouteEstimateResult::failed($this->config->provider(), RouteStatus::NotConfigured);
        }

        $startedAt = microtime(true);
        try {
            $result = $this->estimator->estimate($request);
        } catch (Throwable $e) {
            // Defense in depth — a provider must not crash a consumer even if it
            // violates its no-throw contract.
            $result = RouteEstimateResult::failed($this->config->provider(), RouteStatus::ProviderUnavailable, 'Unexpected routing error.');
        }

        $this->log($request->consumer, $result, (int) round((microtime(true) - $startedAt) * 1000));

        return $result;
    }

    public function geocode(string $address): GeocodeResult
    {
        if (!$this->config->isConfigured()) {
            return GeocodeResult::failed(RouteStatus::NotConfigured);
        }

        try {
            return $this->geocoder->geocode($address);
        } catch (Throwable $e) {
            return GeocodeResult::failed(RouteStatus::ProviderUnavailable, 'Unexpected geocoding error.');
        }
    }

    private function log(string $consumer, RouteEstimateResult $result, int $durationMs): void
    {
        try {
            RouteRequestLog::create([
                'consumer'              => $consumer,
                'provider'              => $result->provider,
                'status'                => $result->status->value,
                'http_status'           => null,
                'duration_ms'           => $durationMs,
                // Provider place references are non-sensitive; full addresses are not stored.
                'origin_reference'      => null,
                'destination_reference' => $result->providerReference,
                'notes'                 => $result->isOk() ? null : substr((string) $result->errorDetail, 0, 240),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to persist routing log.', ['message' => $e->getMessage()]);
        }
    }
}
