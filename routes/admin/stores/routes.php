<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Stores\IndexController;
use App\Http\Controllers\Admin\Stores\CreateController;
use App\Http\Controllers\Admin\Stores\StoreController;
use App\Http\Controllers\Admin\Stores\EditController;
use App\Http\Controllers\Admin\Stores\UpdateController;
use App\Http\Controllers\Admin\Stores\DeleteController;

Route::prefix('stores')
->name('stores.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class);

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');
});
