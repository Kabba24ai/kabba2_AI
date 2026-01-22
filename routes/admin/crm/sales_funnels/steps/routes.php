<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Admin\Crm\SalesFunnels\Steps\StoreController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\Steps\UpdateController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\Steps\DeleteController;


Route::prefix('steps')
->name('steps.')
->group(function () {
    Route::post('/', StoreController::class)->name('store');
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');
    Route::put('/{unique_id}', UpdateController::class)->name('update');

});
