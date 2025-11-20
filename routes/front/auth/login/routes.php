<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Auth\Login\IndexController;
use App\Http\Controllers\Front\Auth\Login\PostController;
use App\Http\Controllers\Front\Auth\Login\ImpersonateController;

Route::prefix('login')->name('login.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/', PostController::class);

    Route::get('impersonate', ImpersonateController::class)->name('impersonate')->middleware('signed');

});
