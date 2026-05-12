<?php

use Illuminate\Support\Facades\Route;


/**
 *
 * Group: time_tracker v1
 * Description: Routes For time_tracker v1
 * Domain:
 *
 */


Route::group(['prefix' => 'v1'], function ($router) {


  /*
    |--------------------------------------------------------------------------
    | Auth Routes
    |--------------------------------------------------------------------------
    */
    require base_path('routes/api/employment_application/v1/stores/routes.php');

    require base_path('routes/api/employment_application/v1/locations/routes.php');

    require base_path('routes/api/employment_application/v1/positions/routes.php');

    require base_path('routes/api/employment_application/v1/applications/routes.php');

    
    require base_path('routes/api/employment_application/v1/auth/routes.php');
    
    require base_path('routes/api/employment_application/v1/profile_settings/routes.php');


    // dashboard
    
    require base_path('routes/api/employment_application/v1/dashboard/routes.php');

    require base_path('routes/api/employment_application/v1/site_content/routes.php');


});
