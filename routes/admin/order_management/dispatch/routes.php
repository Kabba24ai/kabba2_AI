<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Dispatch\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\ShowController;

Route::prefix('dispatch')
->name('dispatch.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');

    // Dispatch detail / driver checklist for one order product
    Route::get('/{unique_id}', [ShowController::class, 'show'])->name('show');
    Route::post('/{unique_id}/checklist', [ShowController::class, 'saveChecklist'])->name('checklist.save');
});
