<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCreatedBroadcast\IndexController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCreatedBroadcast\GetSelectController;


Route::prefix('sms-created-broadcast')
    ->name('sms-created-broadcast.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');

        Route::get('/select', GetSelectController::class)->name('get-select');

    });

