<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question\DeleteController;


Route::prefix('questions')
->name('questions.')
->group(function ($router) {

    Route::post('/store', StoreController::class)->name('store');

    Route::put('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');
});
