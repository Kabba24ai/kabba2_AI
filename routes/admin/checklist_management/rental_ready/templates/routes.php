<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\IndexController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\DeleteController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\CopyController;



Route::prefix('templates')
->name('templates.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');

    Route::post('/{unique_id}/copy', CopyController::class)->name('copy');

    
});
