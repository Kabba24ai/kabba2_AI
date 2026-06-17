<?php

use App\Http\Controllers\Admin\OrderManagement\ScheduleConflicts\IndexController;

Route::prefix('schedule-conflicts')->name('schedule-conflicts.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
