<?php

use Illuminate\Support\Facades\Route;


/**
 *
 * Group: time_tracker
 * Description: Routes For time_tracker
 * Domain:
 *
 */



Route::group(['prefix' => 'time-tracker'], function ($router) {
    // time_tracker V1
    require base_path('routes/api/time_tracker/v1/routes.php');
});
