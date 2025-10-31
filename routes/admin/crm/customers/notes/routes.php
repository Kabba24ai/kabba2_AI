<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\Customers\Notes\StoreController;
use App\Http\Controllers\Admin\Crm\Customers\Notes\FetchController;
use App\Http\Controllers\Admin\Crm\Customers\Notes\UpdateController;
use App\Http\Controllers\Admin\Crm\Customers\Notes\DeleteController;


Route::prefix('notes')
    ->name('notes.')
    ->group(function ($router) {

        Route::get('/fetch/{customer}', FetchController::class)->name('fetch');
        Route::post('/store/{customer}', StoreController::class)->name('store');
        Route::put('/update/{note}', UpdateController::class)->name('update');
        Route::delete('/delete/{note}', DeleteController::class)->name('delete');
});
