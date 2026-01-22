<?php

use Illuminate\Support\Facades\Route;

/**
 *
 * Group: sales_reports
 * Description: Routes For sales_reports
 * Domain:
 *
 */

Route::group(['prefix' => 'sales-reports'], function ($router) {
    // sales_reports V1
    require base_path('routes/api/sales-reports/v1/routes.php');
});
