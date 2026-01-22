<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Auth\ResetPassword\IndexController;
use App\Http\Controllers\Front\Auth\ResetPassword\PostController;
use App\Http\Controllers\Front\Auth\ResetPassword\SuccessController;

Route::prefix('reset-password')->name('reset-password.')->group(function () {

    // Password Reset Flow
    Route::get('/reset', IndexController::class)->name('reset');
    Route::post('/', PostController::class)->name('update');
    Route::get('/success', SuccessController::class)->name('success');
});
