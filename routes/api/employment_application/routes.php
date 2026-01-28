<?php

use Illuminate\Support\Facades\Route;


/**
 *
 * Group: time_tracker
 * Description: Routes For time_tracker
 * Domain:
 *
 */



Route::group(['prefix' => 'employment-application'], function ($router) {
    // time_tracker V1
    require base_path('routes/api/employment_application/v1/routes.php');
});
