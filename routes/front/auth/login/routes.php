<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Auth\Login\IndexController;
use App\Http\Controllers\Front\Auth\Login\PostController;

Route::prefix('login')->name('login.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/', PostController::class);

});
