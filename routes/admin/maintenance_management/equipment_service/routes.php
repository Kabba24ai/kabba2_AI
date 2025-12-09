<?php

use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService\IndexController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Equipment Service Routes
|--------------------------------------------------------------------------
*/

Route::prefix('equipment-service')
    ->name('equipment-service.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });
