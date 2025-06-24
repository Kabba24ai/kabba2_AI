<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;

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
        // Enable Https
        $this->enableHttps();

        // Gate Registration
        $this->gatesRegistration();
    }

    private function enableHttps(): void
    {
        if(config('app.env') !== 'local') {
            \URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }
    }

    private function gatesRegistration(): void
    {
        // Admin Gates
        //require base_path('app/Gates/Admin/index.php');
    }
}
