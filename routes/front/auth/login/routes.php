<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Auth\Login\IndexController;
Route::prefix('login')->name('login.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
});
