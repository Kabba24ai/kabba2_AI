<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Products\IndexController;

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/{slug}/{productVariant}/details', IndexController::class)->name('index');
    Route::post('/{slug}/{productVariant}/details', IndexController::class)->name('details');
});
