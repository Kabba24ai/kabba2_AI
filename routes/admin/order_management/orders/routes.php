<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Orders\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Orders\EditController;

Route::prefix('orders')
->name('orders.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // // Create
    // Route::get('/create', CreateController::class)->name('create');
    // Route::post('/create', IndexController::class);

    // Edit
    Route::get('edit', EditController::class)->name('edit');
    // Route::put('/{unique_id}/edit', IndexController::class);

    // // Reorder
    // Route::get('/reorder', IndexController::class)->name('reorder');
    // Route::post('/reorder', IndexController::class);

    // // Delete
    // Route::delete('/{unique_id}', IndexController::class)->name('delete');
});
