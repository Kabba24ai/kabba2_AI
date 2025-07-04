<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Auth\Logout\IndexController;
Route::prefix('logout')->name('logout.')->group(function () {

    Route::post('/', IndexController::class)->name('index');
});
