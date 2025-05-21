<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route; // ✅ Correct

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        //web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->domain(config('app.domains.front'))
                ->group(base_path('routes/front/routes.php'));

            Route::middleware('web')
                ->domain(config('app.domains.admin'))
                ->group(base_path('routes/admin/routes.php'));

            // Route::middleware(['api'])
            //     ->prefix('api')
            //     ->domain(config('app.domains.api'))
            //     ->group(base_path('routes/api/routes.php'));

        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'prevent-back-history' => \App\Http\Middleware\PreventBackHistoryMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.auth.login'));


        // This 👇
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponseMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
