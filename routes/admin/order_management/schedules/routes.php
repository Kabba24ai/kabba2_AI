<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Schedules\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Schedules\AssignEquipmentController;
use App\Modules\SchedulingAssistant\Http\Controllers\AIScheduleAdvisorController;

Route::prefix('schedules')
->name('schedules.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/assign-equipment', AssignEquipmentController::class)->name('assign-equipment');
    Route::get('/order-product/{orderProductId}/ai', AIScheduleAdvisorController::class)->name('ai.show');

});
