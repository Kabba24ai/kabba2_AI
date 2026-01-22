<?php

use Illuminate\Support\Facades\Route;
// 
use App\Http\Controllers\Front\Auth\Register\IndexController;
use App\Http\Controllers\Front\Auth\Register\PostController;
use App\Http\Controllers\Front\Auth\Register\CheckEmailController;


Route::prefix('register')->name('register.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

    Route::post('/', PostController::class);

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

});
