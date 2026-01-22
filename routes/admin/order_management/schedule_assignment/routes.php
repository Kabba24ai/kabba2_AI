<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment\IndexController;

Route::prefix('schedule-assignment')
    ->name('schedule-assignment.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
});
