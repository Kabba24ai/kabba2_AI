<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

use App\Models\MaintenanceManagement\Equipment;
use App\Observers\EquipmentObserver;

use App\Models\Iam\Personnel\TimeEntry;
use App\Observers\TimeEntryObserver;

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
    }

    private function enableHttps(): void
    {
        if(config('app.env') !== 'local') {
            \URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }else{
            if (env('VITE_ORIGIN_PROTOCOL') === 'https') {
                \URL::forceScheme('https');
                if (!$this->app['request']->isSecure()) {
                    $request = $this->app['request'];
                    $httpsUrl = 'https://' . $request->getHttpHost() . $request->getRequestUri();
                    header('Location: ' . $httpsUrl, true, 301);
                    exit;
                }
            }
        }
    }

    private function gatesRegistration(): void
    {
        // Admin Gates
        //require base_path('app/Gates/Admin/index.php');
    }



}
