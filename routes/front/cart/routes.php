<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Cart\IndexController;
use App\Http\Controllers\Front\Cart\SaveController;

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/save', SaveController::class)->name('save');
});
