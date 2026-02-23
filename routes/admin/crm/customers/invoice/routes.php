<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Customers\Invoice\CreateController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\StoreController;

use App\Http\Controllers\Admin\Crm\Customers\Invoice\IndexController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\OrderDetailsController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\ViewController;

use App\Http\Controllers\Admin\Crm\Customers\Invoice\EditController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\UpdateController;

use App\Http\Controllers\Admin\Crm\Customers\Invoice\SendEmailController;



use App\Http\Controllers\Admin\Crm\Customers\Invoice\DownloadController;



Route::prefix('invoice')
->name('invoice.')
->group(function ($router) {

    Route::get('/{unique_id}',  IndexController::class)->name('index');

    Route::get('{unique_id}/create', CreateController::class)->name('create');
    Route::post('{unique_id}/create', StoreController::class)->name('store');


    Route::get('{unique_id}/show', ViewController::class)->name('show');

    Route::get('{unique_id}/edit', EditController::class)->name('edit');
    Route::post('{unique_id}/update', UpdateController::class)->name('update');

    Route::get('{unique_id}/download', DownloadController::class)->name('download');

    // Route::get('{unique_id}/sendemail', SendEmailController::class)->name('sendemail');
    Route::post('sendemail', SendEmailController::class)
    ->name('sendemail');




    Route::get('/get/orders/details/{unique_id}',  OrderDetailsController::class)->name('orders.details');
});
