<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ProductManagement\Products\IndexController;
use App\Http\Controllers\Admin\ProductManagement\Products\CreateController;
// use App\Http\Controllers\Admin\ProductManagement\Products\StoreController;
// use App\Http\Controllers\Admin\ProductManagement\Products\EditController;
// use App\Http\Controllers\Admin\ProductManagement\Products\UpdateController;
// use App\Http\Controllers\Admin\ProductManagement\Products\ReorderController;
// use App\Http\Controllers\Admin\ProductManagement\Products\UpdateOrderController;
// use App\Http\Controllers\Admin\ProductManagement\Products\DeleteController;


Route::prefix('products')
->name('products.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', IndexController::class);

    // Edit
    Route::get('/{unique_id}/edit', IndexController::class)->name('edit');
    Route::put('/{unique_id}/edit', IndexController::class);

    // Reorder
    Route::get('/reorder', IndexController::class)->name('reorder');
    Route::post('/reorder', IndexController::class);

    // Delete
    Route::delete('/{unique_id}', IndexController::class)->name('delete');
});
