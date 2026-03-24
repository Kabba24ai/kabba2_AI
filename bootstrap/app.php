<?php

use App\Exceptions\ExceptionHandling;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        //web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::domain(config('app.domains.front'))->group(base_path('routes/front/routes.php'));

            Route::middleware('web')->domain(config('app.domains.admin'))->group(base_path('routes/admin/routes.php'));

            Route::middleware(['api'])
                ->prefix('api')
                ->domain(config('app.domains.api'))
                ->group(base_path('routes/api/routes.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

            'role.short' => \App\Http\Middleware\CheckRoleShortName::class,

            'prevent-back-history' => \App\Http\Middleware\PreventBackHistoryMiddleware::class,

            // Front Middleware
            'front.common-front-data' => \App\Http\Middleware\Front\CommonDataMiddleware::class,

            // Admin API middleware
            'admin.common-data' => \App\Http\Middleware\Admin\CommonDataMiddleware::class,

            // Chehck Middleware that customer active
            'customer.active' => \App\Http\Middleware\Front\EnsureCustomerIsActive::class,

            // Restrict kabba.ai customers page to selected admin hosts
            'kabba.ai.domain' => \App\Http\Middleware\RestrictKabbaAiCustomersDomain::class,
        ]);

        $middleware->redirectGuestsTo(function () {
            return request()->getHost() === config('app.domains.front')
                ? route('front.auth.login.index') // Your frontend login
                : route('admin.auth.login'); // Admin login
        });

        // This 👇
        $middleware->api(prepend: [\App\Http\Middleware\ForceJsonResponseMiddleware::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            // 'current_password',
            // 'password',
            // 'password_confirmation',
        ]);
        $exceptions->render(\Closure::fromCallable(['App\Exceptions\ExceptionHandling', 'render']));
    })
    ->create();
