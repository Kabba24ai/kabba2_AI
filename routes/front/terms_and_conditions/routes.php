<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\TermsAndConditions\IndexController;

Route::prefix('terms-and-conditions')->name('terms-and-conditions.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
