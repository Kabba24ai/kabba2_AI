<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Checkout\IndexController;
use App\Http\Controllers\Front\Checkout\PostController;

Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/', PostController::class);
});
