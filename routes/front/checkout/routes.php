<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Checkout\IndexController;
use App\Http\Controllers\Front\Checkout\PostController;
use App\Http\Controllers\Front\Checkout\TaxExemptController;
use App\Http\Controllers\Front\Checkout\ThankYouController;
use App\Http\Controllers\Front\Checkout\ReceiptDownload;



Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/', PostController::class);

    Route::post('tax-exempt', TaxExemptController::class)->name('tax-exempt');

    // Route for handling the thank you page with signed URL
    Route::get('/thank-you/{order}', ThankYouController::class)->name('thank-you')->middleware('signed');

            Route::get('/receipt-download/{order}', ReceiptDownload::class)->name('receipt-download');
});
