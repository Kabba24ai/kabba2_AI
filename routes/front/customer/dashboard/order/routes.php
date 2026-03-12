<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Customer\Dashboard\Order\ReceiptDownload;
use App\Http\Controllers\Front\Customer\Dashboard\Order\ViewController;
use App\Http\Controllers\Front\Customer\Dashboard\Order\SendReceiptEmailController;


Route::prefix('order')->name('order.')->group(function () {


       Route::get('/{unique_id}/receipt-download', ReceiptDownload::class)->name('receipt-download');
        Route::get('/{unique_id}/receipt-email', SendReceiptEmailController::class)->name('receipt-email');

        Route::get('/{id}/view', ViewController::class)->name('view');



});
