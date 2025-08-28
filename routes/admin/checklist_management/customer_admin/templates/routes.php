<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\IndexController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates\DeleteController;



Route::prefix('templates')
->name('templates.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');

    
});
