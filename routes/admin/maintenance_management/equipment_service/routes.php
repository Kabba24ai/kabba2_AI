<?php

use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService\StoreServiceRecordController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService\VerifyAdminCodeController;
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
        Route::post('/record', StoreServiceRecordController::class)->name('record');
        Route::post('/verify-admin-code', VerifyAdminCodeController::class)->name('verify-admin-code');
    });
