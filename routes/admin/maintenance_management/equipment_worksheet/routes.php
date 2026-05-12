<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentWorksheet\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentWorksheet\UpdateController;

/*
|--------------------------------------------------------------------------
| Equipment Worksheet Routes
|--------------------------------------------------------------------------
*/

Route::prefix('equipment-worksheet')
    ->name('equipment-worksheet.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::put('/', UpdateController::class)->name('update');
    });
