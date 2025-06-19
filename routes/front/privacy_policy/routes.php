<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\PrivacyPolicy\IndexController;

Route::prefix('privacy-policy')->name('privacy-policy.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
