<?php

use Illuminate\Support\Facades\Route;

// Controllers


use App\Http\Controllers\Api\TimeTracker\V1\System\ShowSettingsController;
use App\Http\Controllers\Api\TimeTracker\V1\System\UpdateSettingsController;


Route::group(['prefix' => 'system'], function () {

   
        Route::get('/settings', ShowSettingsController::class);
        Route::put('/settings/update', UpdateSettingsController::class);
      

});
