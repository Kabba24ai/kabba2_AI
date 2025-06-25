<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Cart\IndexController;


   
    Route::prefix('cart')->name('cart.')->group(function () {
    
        Route::post('/add', [IndexController::class, 'add'])->name('add');
        Route::get('/', [IndexController::class, 'get'])->name('get');
        Route::post('/remove', [IndexController::class, 'remove'])->name('remove');
        Route::post('/clear', [IndexController::class, 'clear'])->name('clear');

    });
    