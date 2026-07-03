<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\ContactUs\IndexController;
use App\Http\Controllers\Front\ContactUsV2\IndexController as ContactUsV2Controller;

// Existing contact us page — unchanged
Route::prefix('contact-us')->name('contact-us.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});

// Contact Us V2 — builder-driven CMS page
Route::prefix('contact-us-v2')->name('contact-us-v2.')->group(function () {
    Route::get('/', ContactUsV2Controller::class)->name('index');
});
