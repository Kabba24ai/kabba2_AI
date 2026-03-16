<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SalesReports\V1\Auth\LoginController;

/**
 *
 * Group: sales_reports v1
 * Description: Routes For sales_reports v1
 * Domain:
 *
 */

Route::group(['prefix' => 'v1'], function ($router) {
    // Login endpoint for React sales reports module
    Route::post('/login', LoginController::class);
    
    // SSO endpoint for cross-application authentication
    Route::get('/sso', \App\Http\Controllers\Api\SalesReports\V1\Auth\SsoController::class);

    // Sales reports endpoints
    require base_path('routes/api/sales-reports/v1/reports/routes.php');
});
