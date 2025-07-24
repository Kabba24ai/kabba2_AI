<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\PaymentStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\RefundStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\DiscountStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\ChargeStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\UpdateNoteController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\DownloadPdfController;






Route::prefix('customeraccount')
->name('customeraccount.')
->group(function ($router) {


    // store
    Route::post('/payment-store', PaymentStoreController::class)->name('paymentstore');

    Route::post('/refund-store', RefundStoreController::class)->name('refundstore');

    Route::post('/discount-store', DiscountStoreController::class)->name('discountstore');

    Route::post('/charge-store', ChargeStoreController::class)->name('chargestore');

    Route::post('/update-note', UpdateNoteController::class)->name('update_note');
    
    Route::get('/{id}/download', DownloadPdfController::class)->name('download');
});
