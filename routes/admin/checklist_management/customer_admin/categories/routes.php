<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Categories\DeleteController;



Route::prefix('categories')
->name('categories.')
->group(function ($router) {

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');


});
