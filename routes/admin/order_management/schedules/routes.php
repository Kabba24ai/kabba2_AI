<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Schedules\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Schedules\AssignEquipmentController;

Route::prefix('schedules')
->name('schedules.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/assign-equipment', AssignEquipmentController::class)->name('assign-equipment');
});
