<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question\DeleteController;
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Question\CopyController;



Route::prefix('questions')
->name('questions.')
->group(function ($router) {

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');

    Route::post('/{unique_id}/copy', CopyController::class)->name('copy');
    
});
