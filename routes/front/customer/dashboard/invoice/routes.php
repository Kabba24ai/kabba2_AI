<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Customer\Dashboard\Invoice\DownloadPdfController;
use App\Http\Controllers\Front\Customer\Dashboard\Invoice\ViewController;
use App\Http\Controllers\Front\Customer\Dashboard\Invoice\PaymentStoreController;


Route::prefix('invoice')->name('invoice.')->group(function () {

    Route::get('/{id}/download', DownloadPdfController::class)->name('download');

    Route::get('/{id}/view', ViewController::class)->name('view');

    Route::post('/payment-store', PaymentStoreController::class)->name('paymentstore');

});
