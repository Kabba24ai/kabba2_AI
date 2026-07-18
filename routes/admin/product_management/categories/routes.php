<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ProductManagement\Categories\IndexController;
use App\Http\Controllers\Admin\ProductManagement\Categories\CreateController;
use App\Http\Controllers\Admin\ProductManagement\Categories\StoreController;
use App\Http\Controllers\Admin\ProductManagement\Categories\EditController;
use App\Http\Controllers\Admin\ProductManagement\Categories\UpdateController;
use App\Http\Controllers\Admin\ProductManagement\Categories\ReorderController;
use App\Http\Controllers\Admin\ProductManagement\Categories\UpdateOrderController;
use App\Http\Controllers\Admin\ProductManagement\Categories\DeleteController;
use App\Http\Controllers\Admin\ProductManagement\Categories\SubcategorySearchController;
use App\Http\Controllers\Admin\ProductManagement\Categories\ToggleFeaturedController;


Route::prefix('categories')
->name('categories.')
->group(function ($router) {

    //Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create/{categoryId?}', CreateController::class)->name('create');
    Route::post('/create/{categoryId?}', StoreController::class);

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    // Sort Order
    Route::post('/sort-order', UpdateOrderController::class)->name('sort-order');

    // Toggle homepage "Featured Rentals" flag
    Route::post('/{unique_id}/toggle-featured', ToggleFeaturedController::class)->name('toggle-featured');

    // Search subcategories with products
    Route::get('/fetch/subcategories/{search?}', SubcategorySearchController::class)->name('search-subcategories');

    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');
});
