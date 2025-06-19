<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Auth\ResetPassword\IndexController as ResetPasswordIndexController;
use App\Http\Controllers\Front\Auth\ResetPassword\PostController as ResetPasswordPostController;
use App\Http\Controllers\Front\Auth\ResetPassword\SuccessController;

use App\Http\Controllers\Front\AuthController;



// Password Reset Flow
Route::get('/password/reset', ResetPasswordIndexController::class)->name('password.reset');
Route::post('/password/reset', ResetPasswordPostController::class)->name('password.update');
Route::get('/password/reset/success', SuccessController::class)->name('password.reset.success');

// Auth Routes
Route::get('/login', [AuthController::class, 'login'])->name('front.login');
Route::get('/register', [AuthController::class, 'Register'])->name('front.Register');

