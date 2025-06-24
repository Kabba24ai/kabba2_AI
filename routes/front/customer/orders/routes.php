<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Customer\Orders\IndexController;


    Route::post('/cart/add', [IndexController::class, 'add'])->name('cart.add');
    Route::get('/cart', [IndexController::class, 'get'])->name('cart.get');
    Route::post('/cart/remove', [IndexController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [IndexController::class, 'clear'])->name('cart.clear');
