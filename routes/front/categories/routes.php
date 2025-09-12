<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Categories\IndexController;
use App\Http\Controllers\Front\Categories\ChildController;

Route::prefix('product-categories')->name('categories.')->group(function () {

    Route::get('{slug}', IndexController::class)->name('index');
    Route::get('/{slug}/{childCategorySlug}', ChildController::class)->name('sub-category');
});
