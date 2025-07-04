<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Customer\Dashboard\IndexController;


Route::prefix('dashboard')->name('dashboard.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
    
});
