<?php

use Illuminate\Support\Facades\Route;
// 
use App\Http\Controllers\Front\Auth\ForgotPassword\IndexController;


Route::prefix('forgot-password')->name('forgot-password.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

});
