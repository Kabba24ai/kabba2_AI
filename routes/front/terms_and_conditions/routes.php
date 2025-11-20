<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\TermsAndConditions\IndexController;
use App\Http\Controllers\Front\TermsAndConditions\PostController;
use App\Http\Controllers\Front\TermsAndConditions\GeneralController;


Route::prefix('terms-and-conditions')->name('terms-and-conditions.')->group(function () {
    Route::get('/{orderUniqueId}/{device?}', IndexController::class)->name('index');
    Route::post('/{orderUniqueId}/sign', PostController::class)->name('sign');

    Route::get('/', GeneralController::class)->name('general');
});
