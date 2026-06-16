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

Route::prefix('order-management')
->name('order-management.')
->group(function ($router) {

    // orders
    require base_path('routes/admin/order_management/orders/routes.php');

    // schedules
    require base_path('routes/admin/order_management/schedules/routes.php');

    // dispatch (driver-focused, Truck-only view)
    require base_path('routes/admin/order_management/dispatch/routes.php');

    // schedule assignment
    require base_path('routes/admin/order_management/schedule_assignment/routes.php');

    // equipment inventory
    require base_path('routes/admin/order_management/equipment_inventory/routes.php');

    // schedule conflicts
    require base_path('routes/admin/order_management/schedule_conflicts/routes.php');


    require base_path('routes/admin/order_management/invoice/routes.php');

    require base_path('routes/admin/order_management/inventory_equipment/routes.php');


});
