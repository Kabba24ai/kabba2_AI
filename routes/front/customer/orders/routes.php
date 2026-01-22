<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Customer\Orders\IndexController;


Route::prefix('orders')->name('orders.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

});
