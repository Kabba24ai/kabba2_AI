<?php

use Illuminate\Support\Facades\Route;

/**
 *
 * Group: sales_reports v1
 * Description: Routes For sales_reports v1
 * Domain:
 *
 */

Route::group(['prefix' => 'v1'], function ($router) {
    // Sales reports endpoints
    require base_path('routes/api/sales-reports/v1/reports/routes.php');
});
