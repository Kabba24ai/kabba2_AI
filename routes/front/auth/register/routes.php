<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Auth\Register\IndexController;
Route::prefix('register')->name('register.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
});
