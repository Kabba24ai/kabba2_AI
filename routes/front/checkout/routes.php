<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Checkout\IndexController;

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
