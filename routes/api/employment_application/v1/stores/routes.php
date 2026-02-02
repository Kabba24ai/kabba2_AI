<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Stores\ListStoresController;

Route::group(['prefix' => 'stores'], function () {
        // list Stores
        Route::get('/', ListStoresController::class);
});
