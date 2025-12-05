<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory\StoreController;

use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory\IndexController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory\ShowController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory\UpdateController;

use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCategory\DeleteController;


Route::prefix('sms-category')
    ->name('sms-category.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');
        Route::post('/store', StoreController::class)->name('store');
        Route::get('/{unique_id}', ShowController::class)->name('show');
        Route::put('/{unique_id}/update', UpdateController::class)->name('update');
        Route::delete('/{unique_id}', DeleteController::class)->name('delete');

    });

