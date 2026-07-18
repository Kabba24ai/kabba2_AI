<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Stores\ShowController;

Route::prefix('stores')->name('stores.')->group(function () {
    Route::get('/{slug}', ShowController::class)->name('show');
});
