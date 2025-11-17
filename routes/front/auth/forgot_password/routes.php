<?php

use Illuminate\Support\Facades\Route;
//
use App\Http\Controllers\Front\Auth\ForgotPassword\IndexController;
use App\Http\Controllers\Front\Auth\ForgotPassword\SendOtpController;


Route::prefix('forgot-password')->name('forgot-password.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

    Route::post('/send-otp', SendOtpController::class)->name('sendOtp');
});
