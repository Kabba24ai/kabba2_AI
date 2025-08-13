<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\PaymentStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\RefundStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\DiscountStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\ChargeStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\UpdateNoteController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\DownloadPdfController;

use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\UpdateController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\DeleteController;


Route::prefix('customer-account')
->name('customer-account.')
->group(function ($router) {

    // store
    Route::post('/payment-store', PaymentStoreController::class)->name('paymentstore');

    Route::post('/refund-store', RefundStoreController::class)->name('refundstore');

    Route::post('/discount-store', DiscountStoreController::class)->name('discountstore');

    Route::post('/charge-store', ChargeStoreController::class)->name('chargestore');

    Route::post('/update-note', UpdateNoteController::class)->name('update_note');
    
    Route::get('/{id}/download', DownloadPdfController::class)->name('download');

    Route::PUT('/{id}/transactionupdate', UpdateController::class)->name('transactionupdate');
    
    Route::delete('/{id}/delete', DeleteController::class)->name('transactiondelete');

});
