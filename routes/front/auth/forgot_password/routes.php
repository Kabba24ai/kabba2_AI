<?php

use Illuminate\Support\Facades\Route;
//
use App\Http\Controllers\Front\Auth\ForgotPassword\IndexController;

use App\Http\Controllers\Front\Auth\ForgotPassword\CheckCustomerController;

use App\Http\Controllers\Front\Auth\ForgotPassword\SendResetPasswordTokenController;
use App\Http\Controllers\Front\Auth\ForgotPassword\ResetPasswordFormController;
use App\Http\Controllers\Front\Auth\ForgotPassword\UpdatePasswordController;





Route::prefix('forgot-password')->name('forgot-password.')->group(function () {

    Route::any('/', IndexController::class)->name('index');

    Route::get('/check-customer', CheckCustomerController::class)
        ->name('check.customer');


    Route::post('/send-token', SendResetPasswordTokenController::class)->name('send.token');

    Route::get('/reset-password/{token}', ResetPasswordFormController::class)
        ->name('reset-password.form');

    Route::post('/reset-password', UpdatePasswordController::class)
        ->name('reset-password.update');
});
