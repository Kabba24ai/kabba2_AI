<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Customer\Profile\IndexController;
use App\Http\Controllers\Front\Customer\Profile\PostController;



Route::prefix('profile')->name('profile.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
    
    Route::post('/',PostController::class);

});
