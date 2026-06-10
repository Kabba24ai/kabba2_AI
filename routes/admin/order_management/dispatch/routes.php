<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Dispatch\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\ShowController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\DriverSummaryController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\PriorityController;

Route::prefix('dispatch')
->name('dispatch.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
    Route::get('/driver-cards', [IndexController::class, 'driverCards'])->name('driver-cards');
    Route::get('/drivers', DriverSummaryController::class)->name('drivers');

    // Dispatch detail / driver checklist for one order product
    Route::get('/{unique_id}', [ShowController::class, 'show'])->name('show');
    Route::post('/{unique_id}/checklist', [ShowController::class, 'saveChecklist'])->name('checklist.save');
    Route::post('/{unique_id}/priority', PriorityController::class)->name('priority');
});
