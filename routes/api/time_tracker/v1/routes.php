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
    require base_path('routes/api/time_tracker/v1/auth/routes.php');
 

        


    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */
       Route::middleware(['auth:sanctum'])->group(function () {

              require base_path('routes/api/time_tracker/v1/time_clock/routes.php');
              require base_path('routes/api/time_tracker/v1/attendance/routes.php');
              require base_path('routes/api/time_tracker/v1/vacation_summary/routes.php');


              /*
        |--------------------------------------------------------------------------
        | Admin Only Routes
        |--------------------------------------------------------------------------
        */

              Route::middleware(['role.short:admin'])->group(function () {
                     require base_path('routes/api/time_tracker/v1/users/routes.php');
                     require base_path('routes/api/time_tracker/v1/system/routes.php');
                     
                     require base_path('routes/api/time_tracker/v1/vacation/routes.php');

                     require base_path('routes/api/time_tracker/v1/time_reports/routes.php');
              });

       });
});
