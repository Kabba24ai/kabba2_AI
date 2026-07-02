<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Stores\ShowController;

Route::prefix('stores')->name('stores.')->group(function () {
    Route::get('/{unique_id}', ShowController::class)->name('show');
});
