<?php

namespace App\Enums\Routing;

/**
 * The single, shared outcome vocabulary for a routing/geocoding attempt —
 * used by every consumer of App\Services\Routing (Field Service now, Dispatch
 * later). A consumer never inspects a provider's raw HTTP status; it branches
 * on these normalized cases so provider-specific failures never leak upward.
 */
enum RouteStatus: string
{
    /** A route (and, where needed, geocoding) was computed successfully. */
    case Ok = 'ok';

    /** No provider/credentials configured — the integration is switched off. */
    case NotConfigured = 'not_configured';

    /** Credentials present but rejected by the provider (bad/blocked key). */
    case InvalidCredentials = 'invalid_credentials';

    /** An address could not be resolved to coordinates. */
    case GeocodingFailure = 'geocoding_failure';

    /** Origin and destination are valid but no drivable route connects them. */
    case NoRoute = 'no_route';

    /** The provider throttled the request. */
    case RateLimit = 'rate_limit';

    /** The request exceeded the configured timeout. */
    case Timeout = 'timeout';

    /** The provider was unreachable or returned an unexpected server error. */
    case ProviderUnavailable = 'provider_unavailable';

    /** The supplied origin was empty/unusable before any provider call. */
    case InvalidOrigin = 'invalid_origin';

    /** The supplied destination was empty/unusable before any provider call. */
    case InvalidDestination = 'invalid_destination';

    public function label(): string
    {
        return match ($this) {
            self::Ok                  => 'Route calculated',
            self::NotConfigured       => 'Routing not configured',
            self::InvalidCredentials  => 'Invalid API credentials',
            self::GeocodingFailure    => 'Address could not be located',
            self::NoRoute             => 'No route found',
            self::RateLimit           => 'Provider rate limit reached',
            self::Timeout             => 'Routing request timed out',
            self::ProviderUnavailable => 'Routing provider unavailable',
            self::InvalidOrigin       => 'Invalid departure location',
            self::InvalidDestination  => 'Invalid destination',
        };
    }

    /** A successful estimate carrying distance/duration/arrival. */
    public function isOk(): bool
    {
        return $this === self::Ok;
    }

    /**
     * A transient/environmental failure the consumer may reasonably retry or
     * defer (as opposed to a hard input/credential error the user must fix).
     */
    public function isTransient(): bool
    {
        return in_array($this, [self::RateLimit, self::Timeout, self::ProviderUnavailable], true);
    }
}
