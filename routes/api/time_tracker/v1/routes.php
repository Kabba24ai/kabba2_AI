<?php

use Illuminate\Support\Facades\Route;


/**
 *
 * Group: admin v1
 * Description: Routes For admin v1
 * Domain:
 *
 */


Route::group(['prefix' => 'v1'], function ($router) {
    // auth
    require base_path('routes/api/time_tracker/v1/auth/routes.php');
 
       Route::middleware(['auth:sanctum'])->group(function () {

              require base_path('routes/api/time_tracker/v1/time_clock/routes.php');
              require base_path('routes/api/time_tracker/v1/attendance/routes.php');
              require base_path('routes/api/time_tracker/v1/vacation_summary/routes.php');


              // admin role

              Route::middleware(['role.short:admin'])->group(function () {
                     require base_path('routes/api/time_tracker/v1/users/routes.php');
                     require base_path('routes/api/time_tracker/v1/system/routes.php');
                     
                     require base_path('routes/api/time_tracker/v1/vacation/routes.php');
              });

       });
});
