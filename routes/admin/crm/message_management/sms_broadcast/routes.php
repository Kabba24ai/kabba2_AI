<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\StoreController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\IndexController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\ShowController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\UpdateController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\DeleteController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\SendController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsBroadcast\CopyController;




Route::prefix('sms-broadcast')
    ->name('sms-broadcast.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');
        Route::post('/store', StoreController::class)->name('store');
        Route::get('/{id}', ShowController::class)->name('show');
        Route::put('/{id}/update', UpdateController::class)->name('update');
        Route::delete('/{id}', DeleteController::class)->name('delete');
Route::get('/copy/{id}', CopyController::class)->name('copy');


        Route::post('/send/{id}', SendController::class)->name('send');


    });

