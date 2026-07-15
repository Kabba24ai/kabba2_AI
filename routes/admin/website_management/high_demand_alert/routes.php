<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\HighDemandAlert\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\HighDemandAlert\UpdateController;

Route::prefix('high-demand-alert')
    ->name('high-demand-alert.')
    ->group(function () {

        // Global High Demand Alert editor
        Route::get('/', IndexController::class)->name('index');

        // Save alert settings
        Route::post('/update', UpdateController::class)->name('update');

    });
