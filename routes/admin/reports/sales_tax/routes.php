<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Reports\SalesTax\IndexController;
use App\Http\Controllers\Admin\Reports\SalesTax\PaymentViewController;


Route::prefix('sales-tax')
->name('sales-tax.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/{unique_id}', PaymentViewController::class)->name('paymentview');
});
