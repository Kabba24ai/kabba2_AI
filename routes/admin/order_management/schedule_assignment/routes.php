<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment\IndexController;
use App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment\AutoAssignDirectController;

Route::prefix('schedule-assignment')
    ->name('schedule-assignment.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/auto-assign-direct', AutoAssignDirectController::class)->name('auto-assign-direct');
});
