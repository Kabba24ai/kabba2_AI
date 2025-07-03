<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Checkout\IndexController;
use App\Http\Controllers\Front\Checkout\PostController;
use App\Http\Controllers\Front\Checkout\ThankYouController;


Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/', PostController::class);

    Route::get('/thank-you/{order}', ThankYouController::class)->name('thank-you')->middleware('signed');


});
