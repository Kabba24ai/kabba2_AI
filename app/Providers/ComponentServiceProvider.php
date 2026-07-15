<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Website\ComponentRegistry;
use App\Services\Website\Components\HeroComponent;
use App\Services\Website\Components\ContactStripComponent;
use App\Services\Website\Components\FeaturedRentalsComponent;
use App\Services\Website\Components\FeatureStripComponent;
use App\Services\Website\Components\FooterComponent;
use App\Services\Website\Components\SeoComponent;
use App\Services\Website\Components\LocationsComponent;
use App\Services\Website\Components\QuestionCtaComponent;

class ComponentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ComponentRegistry::class, function () {
            return (new ComponentRegistry())
                ->register(new HeroComponent())
                ->register(new ContactStripComponent())
                ->register(new FeaturedRentalsComponent())
                ->register(new FeatureStripComponent())
                ->register(new FooterComponent())
                ->register(new SeoComponent())
                ->register(new LocationsComponent())
                ->register(new QuestionCtaComponent());
        });
    }
}
