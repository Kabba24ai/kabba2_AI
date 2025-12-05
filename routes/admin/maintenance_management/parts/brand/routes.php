<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand\FetchController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand\UpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand\DeleteController;





Route::prefix('brand')
    ->name('brand.')
    ->group(function ($router) {

        Route::get('/fetch', FetchController::class)->name('fetch');
        Route::post('/store', StoreController::class)->name('store');
        Route::post('/update/{brand}', UpdateController::class)->name('update');
        Route::delete('/delete/{brand}', DeleteController::class)->name('delete');
        
});
