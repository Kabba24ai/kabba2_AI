<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Customers\Invoice\CreateController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\StoreController;

use App\Http\Controllers\Admin\Crm\Customers\Invoice\IndexController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\OrderDetailsController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\ViewController;


Route::prefix('invoice')
->name('invoice.')
->group(function ($router) {

    Route::get('/{unique_id}',  IndexController::class)->name('index');

    Route::get('{unique_id}/create', CreateController::class)->name('create');
    Route::post('{unique_id}/create', StoreController::class)->name('create');


    Route::get('{unique_id}/show', ViewController::class)->name('show');


    Route::get('/get/orders/details/{unique_id}',  OrderDetailsController::class)->name('orders.details');
});
