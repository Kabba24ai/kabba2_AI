<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\SalesFunnels\Categories\IndexController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\Categories\DeleteController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\Categories\StoreController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\Categories\UpdateController;

Route::prefix('categories')
->name('categories.')
->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/', StoreController::class)->name('store');
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');
    Route::put('/{unique_id}', UpdateController::class)->name('update');
});
