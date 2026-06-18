<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('reports')
->name('reports.')
->group(function ($router) {

    // sales tax
    require base_path('routes/admin/reports/sales_tax/routes.php');

    // sales reports (Pure Sales Summary + future reports)
    require base_path('routes/admin/reports/sales_reports/routes.php');

    // calls log
    require base_path('routes/admin/reports/calls_log/routes.php');

    // fuel charge alerts
    require base_path('routes/admin/reports/fuel_charge_alerts/routes.php');

    // new damage alerts
    require base_path('routes/admin/reports/new_damage_alerts/routes.php');

});
