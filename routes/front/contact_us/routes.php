<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\ContactUs\IndexController;

// Contact Us — builder-driven CMS page (canonical route)
Route::prefix('contact-us')->name('contact-us.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
