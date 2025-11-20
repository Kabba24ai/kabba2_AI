<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category\FetchController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category\UpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\Category\DeleteController;


Route::prefix('category')
    ->name('category.')
    ->group(function ($router) {

        Route::get('/fetch', FetchController::class)->name('fetch');
        Route::post('/store', StoreController::class)->name('store');
        Route::put('/update/{category}', UpdateController::class)->name('update');
        Route::delete('/delete/{category}', DeleteController::class)->name('delete');
        
});
