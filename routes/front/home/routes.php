<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Home\IndexController;

Route::prefix('/')->name('home.')->group(function () {

    Route::get('/', IndexController::class)->name('index');
});
