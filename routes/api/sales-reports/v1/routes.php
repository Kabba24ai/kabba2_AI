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
    // Public: token issuance only. Everything that serves reporting data is
    // protected below. (Phase 3A — authentication only; no role/permission
    // or store-level authorization is introduced here by design.)
    Route::post('/login', LoginController::class);

    // SSO endpoint for cross-application authentication
    Route::get('/sso', \App\Http\Controllers\Api\SalesReports\V1\Auth\SsoController::class);

    // Sales reports endpoints — any authenticated employee may view.
    // Anonymous/invalid-token requests receive a JSON 401 (ForceJsonResponse
    // makes the guard's AuthenticationException render as JSON, never an HTML
    // login redirect).
    Route::middleware(['auth:api_user'])->group(function () {
        require base_path('routes/api/sales-reports/v1/reports/routes.php');
    });
});
