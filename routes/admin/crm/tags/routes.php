<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\Tags\StoreController;
use App\Http\Controllers\Admin\Crm\Tags\FetchController;
use App\Http\Controllers\Admin\Crm\Tags\UpdateController;
use App\Http\Controllers\Admin\Crm\Tags\DeleteController;


Route::prefix('tags')
    ->name('tags.')
    ->group(function ($router) {

        Route::get('/fetch', FetchController::class)->name('fetch');
        Route::post('/store', StoreController::class)->name('store');
        Route::put('/update/{tag}', UpdateController::class)->name('update');
        Route::delete('/delete/{tag}', DeleteController::class)->name('delete');
        
});
