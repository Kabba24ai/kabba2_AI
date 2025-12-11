<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\StoreController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\IndexController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\ShowController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\UpdateController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\DeleteController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\SendController;
use App\Http\Controllers\Admin\Crm\MessageManagement\SmsFunnel\CopyController;




Route::prefix('sms-funnel')
    ->name('sms-funnel.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');
        Route::post('/store', StoreController::class)->name('store');
        Route::get('/{id}', ShowController::class)->name('show');
        Route::put('/{id}/update', UpdateController::class)->name('update');
        Route::delete('/{id}', DeleteController::class)->name('delete');
Route::get('/copy/{id}', CopyController::class)->name('copy');


        Route::post('/send/{id}', SendController::class)->name('send');


    });

