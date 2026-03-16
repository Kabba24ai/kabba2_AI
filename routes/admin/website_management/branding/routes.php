<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\Branding\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\Branding\SaveController;

Route::prefix('branding')
    ->name('branding.')
     ->group(function () {

        // Branding page
        Route::get('/', IndexController::class)
            ->name('index');

        // Save branding settings
        Route::post('/update', SaveController::class)
            ->name('update');

    });
