<?php

use App\Http\Controllers\Admin\ServiceManagement\OverviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('service-management')
    ->name('service-management.')
    ->group(function () {
        Route::get('/', OverviewController::class)->name('overview');
    });
