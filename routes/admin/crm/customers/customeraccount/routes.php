<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\PaymentStoreController;
use App\Http\Controllers\Admin\Crm\Customers\CustomerAccount\RefundStoreController;



Route::prefix('customeraccount')
->name('customeraccount.')
->group(function ($router) {


    // store
    Route::post('/payment-store', PaymentStoreController::class)->name('paymentstore');

    Route::post('/refund-store', RefundStoreController::class)->name('refundstore');

    


});
