<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Products\IndexController;

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/{categorySlug}/{slug}/{productType}/details', IndexController::class)->name('details');
});
