<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Question\StoreController;


Route::prefix('questions')
->name('questions.')
->group(function ($router) {

    Route::post('/store', StoreController::class)->name('store');

});
