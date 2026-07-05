<?php

use App\Http\Controllers\Admin\ServiceManagement\OverviewController;
use App\Http\Controllers\Admin\ServiceManagement\Tickets;
use Illuminate\Support\Facades\Route;

Route::prefix('service-management')
    ->name('service-management.')
    ->group(function () {
        Route::get('/', OverviewController::class)->name('overview');

        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                 Tickets\IndexController::class)->name('index');
            Route::get('/create',           Tickets\CreateController::class)->name('create');
            Route::post('/',                Tickets\StoreController::class)->name('store');
            Route::get('/{ticket}',         Tickets\ShowController::class)->name('show');
            Route::get('/{ticket}/edit',    Tickets\EditController::class)->name('edit');
            Route::put('/{ticket}',         Tickets\UpdateController::class)->name('update');
            Route::post('/{ticket}/status', Tickets\StatusController::class)->name('status');
        });
    });
