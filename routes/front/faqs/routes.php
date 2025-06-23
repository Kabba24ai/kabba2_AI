<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Faqs\IndexController;

Route::prefix('faqs')->name('faqs.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
