<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ProductManagement\Products\IndexController;
use App\Http\Controllers\Admin\ProductManagement\Products\CreateController;
use App\Http\Controllers\Admin\ProductManagement\Products\StoreController;
use App\Http\Controllers\Admin\ProductManagement\Products\EditController;
use App\Http\Controllers\Admin\ProductManagement\Products\UpdateController;
use App\Http\Controllers\Admin\ProductManagement\Products\DeleteController;
use App\Http\Controllers\Admin\ProductManagement\Products\FetchOptionsController;
use App\Http\Controllers\Admin\ProductManagement\Products\ProductSearchController;
use App\Http\Controllers\Admin\ProductManagement\Products\CopyController;

Route::prefix('products')
->name('products.')
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

    // Copy
    Route::post('/{unique_id}/copy', CopyController::class)->name('copy');

    // Fetch Options
    Route::get('/fetch/options/{optionId}', FetchOptionsController::class)->name('fetch-options');
    Route::get('/fetch/{search}/{currentProductUniqueId?}', ProductSearchController::class)->name('search');
});
