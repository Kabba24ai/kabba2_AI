<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Positions\ListPositionsController;

Route::group(['prefix' => 'positions'], function () {
        // list Stores
        Route::get('/', ListPositionsController::class);
});
