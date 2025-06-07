<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Schedules\IndexController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\CreateController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\StoreController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\EditController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\UpdateController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\ReorderController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\UpdateOrderController;
// use App\Http\Controllers\Admin\ProductManagement\schedules\DeleteController;


Route::prefix('schedules')
->name('schedules.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // // Create
    // Route::get('/create', CreateController::class)->name('create');
    // Route::post('/create', IndexController::class);

    // // Edit
    // Route::get('/{unique_id}/edit', IndexController::class)->name('edit');
    // Route::put('/{unique_id}/edit', IndexController::class);

    // // Reorder
    // Route::get('/reorder', IndexController::class)->name('reorder');
    // Route::post('/reorder', IndexController::class);

    // // Delete
    // Route::delete('/{unique_id}', IndexController::class)->name('delete');
});
