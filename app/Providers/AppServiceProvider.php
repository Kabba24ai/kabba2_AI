<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Helpers\ConfigurationHelper;
use Illuminate\Support\Facades\Config;


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

        // Dynamic Mail Configuration
        $this->configureMailFromDatabase();
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

    /**
     * Dynamically override mail config from DB settings.
     */
    private function configureMailFromDatabase(): void
    {
        try {
            Config::set('mail.default', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_mailer') ?? config('mail.default'));
            Config::set('mail.mailers.smtp.host', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_host') ?? config('mail.mailers.smtp.host'));
            Config::set('mail.mailers.smtp.port', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_port') ?? config('mail.mailers.smtp.port'));
            Config::set('mail.mailers.smtp.username', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_username') ?? config('mail.mailers.smtp.username'));
            Config::set('mail.mailers.smtp.password', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_password') ?? config('mail.mailers.smtp.password'));
            Config::set('mail.mailers.smtp.encryption', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_encryption') ?? config('mail.mailers.smtp.encryption'));
            Config::set('mail.from.address', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_from_address') ?? config('mail.from.address'));
            Config::set('mail.from.name', ConfigurationHelper::getSettings('Mail Send Settings', 'mail_from_name') ?? config('mail.from.name'));
        } catch (\Exception $e) {
            // Database might not be ready yet (migrations/seeding), so fallback to .env
        }
    }

}
