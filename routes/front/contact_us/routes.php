<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\ContactUs\IndexController;

Route::prefix('contact-us')->name('contact-us.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
