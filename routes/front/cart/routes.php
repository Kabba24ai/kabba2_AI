<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Cart\IndexController;


Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [IndexController::class, 'get'])->name('get');
});
