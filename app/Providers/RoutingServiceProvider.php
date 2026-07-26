<?php

namespace App\Providers;

use App\Services\Routing\Contracts\Geocoder;
use App\Services\Routing\Contracts\RouteEstimator;
use App\Services\Routing\Contracts\RoutingConfigProvider;
use App\Services\Routing\Providers\GoogleMapsGeocoder;
use App\Services\Routing\Providers\GoogleMapsRouteEstimator;
use App\Services\Routing\RoutingService;
use App\Services\Routing\Settings\ConfigurationRoutingConfigProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the shared, platform-level routing layer. Bindings are provider-neutral
 * at the interface seam: today they resolve to the Google implementations, but
 * a consumer only ever depends on RoutingService / the contracts, so swapping
 * or adding a provider is a change here alone.
 *
 * The config is read from the admin-managed Configurations store; tests or a
 * future env-based deployment can rebind RoutingConfigProvider without touching
 * providers or consumers.
 */
class RoutingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RoutingConfigProvider::class, ConfigurationRoutingConfigProvider::class);

        $this->app->singleton(Geocoder::class, function ($app) {
            return new GoogleMapsGeocoder($app->make(RoutingConfigProvider::class));
        });

        $this->app->singleton(RouteEstimator::class, function ($app) {
            return new GoogleMapsRouteEstimator(
                $app->make(RoutingConfigProvider::class),
                $app->make(Geocoder::class),
            );
        });

        $this->app->singleton(RoutingService::class, function ($app) {
            return new RoutingService(
                $app->make(RoutingConfigProvider::class),
                $app->make(RouteEstimator::class),
                $app->make(Geocoder::class),
            );
        });
    }
}
