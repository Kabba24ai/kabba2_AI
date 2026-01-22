<?php

use Illuminate\Support\Facades\Route;


/**
 *
 * Group: admin
 * Description: Routes For admin
 * Domain:
 *
 */



Route::group(['prefix' => 'admin'], function ($router) {
    // Admin V1
    require base_path('routes/api/admin/v1/routes.php');
});
