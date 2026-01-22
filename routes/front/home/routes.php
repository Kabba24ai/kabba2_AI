<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\Home\IndexController;

use App\Http\Controllers\Front\Home\SearchController;

Route::prefix('/')->name('home.')->group(function () {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/search', SearchController::class)->name('search.autocomplete');
});
