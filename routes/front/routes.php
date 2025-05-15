<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Auth\ResetPassword\IndexController as ResetPasswordIndexController;
use App\Http\Controllers\Front\Auth\ResetPassword\PostController as ResetPasswordPostController;
use App\Http\Controllers\Front\Auth\ResetPassword\SuccessController;

// Static Pages
Route::view('/privacy-policy', 'front.privacy-policy')->name('front.privacy');
Route::view('/terms-and-conditions', 'front.terms-and-conditions')->name('front.terms');

// Password Reset Flow
Route::get('/password/reset', ResetPasswordIndexController::class)->name('password.reset');
Route::post('/password/reset', ResetPasswordPostController::class)->name('password.update');
Route::get('/password/reset/success', SuccessController::class)->name('password.reset.success');
