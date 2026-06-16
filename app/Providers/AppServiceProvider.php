<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Models\MaintenanceManagement\Equipment;
use App\Observers\EquipmentObserver;

use App\Models\Iam\Personnel\TimeEntry;
use App\Observers\TimeEntryObserver;

use App\Models\ProductManagement\Product;
use App\Observers\ProductObserver;

use App\Models\ProductManagement\ProductCategory;
use App\Observers\ProductCategoryObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        // Enable Https
        $this->enableHttps();

        // Gate Registration
        $this->gatesRegistration();

        //  REGISTER OBSERVER
        Equipment::observe(EquipmentObserver::class);
        TimeEntry::observe(TimeEntryObserver::class);

        // AI Visibility Layer — auto-generate schema on product/category save
        Product::observe(ProductObserver::class);
        ProductCategory::observe(ProductCategoryObserver::class);
    }

    private function enableHttps(): void
    {
        if (config('app.vite_origin_protocol') === 'https') {
            \URL::forceScheme('https');
            if (!$this->app['request']->isSecure()) {
                $request = $this->app['request'];
                $httpsUrl = 'https://' . $request->getHttpHost() . $request->getRequestUri();
                header('Location: ' . $httpsUrl, true, 301);
                exit;
            }
        }
    }

    private function gatesRegistration(): void
    {
        // Admin Gates
        //require base_path('app/Gates/Admin/index.php');
    }



}
