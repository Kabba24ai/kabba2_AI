<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Auth\Login\IndexController;
use App\Http\Controllers\Admin\Auth\Login\PostController;
use App\Http\Controllers\Admin\Auth\Logout\IndexController as LogoutIndexController;
use App\Http\Controllers\Admin\Auth\ResetPassword\IndexController as ResetPasswordIndexController;
use App\Http\Controllers\Admin\Auth\ResetPassword\UpdateController as ResetPasswordUpdateController;


Route::name('auth.')
->group(function($router){
    Route::get('/', IndexController::class)->name('login');
    Route::post('/', PostController::class);

    Route::get('/logout', LogoutIndexController::class)->name('logout');

    Route::get('/reset-password/{token}', ResetPasswordIndexController::class)->name('reset_password');
    Route::post('/reset-password/{token}', ResetPasswordUpdateController::class);
});
