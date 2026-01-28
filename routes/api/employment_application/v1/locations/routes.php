<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Locations\ListStatesController;

Route::group(['prefix' => 'locations'], function () {
        // list Stores
        Route::get('/states', ListStatesController::class);
});
