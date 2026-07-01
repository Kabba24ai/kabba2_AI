<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\HomeV2\IndexController;

Route::prefix('home-v2')->name('home_v2.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

});
