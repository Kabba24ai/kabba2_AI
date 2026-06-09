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

Route::prefix('maintenance-management')
->name('maintenance-management.')
->group(function ($router) {

    // orders
    require base_path('routes/admin/maintenance_management/equipment/routes.php');
    require base_path('routes/admin/maintenance_management/equipment_worksheet/routes.php');
    require base_path('routes/admin/maintenance_management/equipment_service/routes.php');
    require base_path('routes/admin/maintenance_management/parts/routes.php');
    require base_path('routes/admin/maintenance_management/suppliers/routes.php');
    require base_path('routes/admin/maintenance_management/service_master/routes.php');
    require base_path('routes/admin/maintenance_management/equipment_ai/routes.php');

});
