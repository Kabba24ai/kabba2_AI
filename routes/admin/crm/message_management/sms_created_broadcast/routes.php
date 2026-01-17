<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\Crm\MessageManagement\SmsCreatedBroadcast\IndexController;

Route::prefix('sms-created-broadcast')
    ->name('sms-created-broadcast.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');

    });

