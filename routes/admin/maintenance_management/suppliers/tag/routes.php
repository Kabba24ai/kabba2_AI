<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag\FetchController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag\UpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Tag\DeleteController;


Route::prefix('tag')
    ->name('tag.')
    ->group(function ($router) {

        Route::get('/fetch', FetchController::class)->name('fetch');
        Route::post('/store', StoreController::class)->name('store');
        Route::put('/update/{tag}', UpdateController::class)->name('update');
        Route::delete('/delete/{tag}', DeleteController::class)->name('delete');
        
});
