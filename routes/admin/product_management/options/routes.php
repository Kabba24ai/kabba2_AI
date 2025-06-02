<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ProductManagement\Options\IndexController;
use App\Http\Controllers\Admin\ProductManagement\Options\CreateController;
// use App\Http\Controllers\Admin\ProductManagement\Options\StoreController;
// use App\Http\Controllers\Admin\ProductManagement\Options\EditController;
// use App\Http\Controllers\Admin\ProductManagement\Options\UpdateController;
// use App\Http\Controllers\Admin\ProductManagement\Options\DeleteController;


Route::prefix('options')
->name('options.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', IndexController::class);

    // Edit
    Route::get('/{unique_id}/edit', IndexController::class)->name('edit');
    Route::put('/{unique_id}/edit', IndexController::class);

    // Delete
    Route::delete('/{unique_id}', IndexController::class)->name('delete');
});
