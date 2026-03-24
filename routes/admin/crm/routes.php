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

Route::prefix('crm')
->name('crm.')
->group(function ($router) {

    // customers
    require base_path('routes/admin/crm/customers/routes.php');

    require base_path('routes/admin/crm/billing_summary/routes.php');

    require base_path('routes/admin/crm/funnels/routes.php');

    require base_path('routes/admin/crm/tags/routes.php');

    //message_management
    require base_path('routes/admin/crm/message_management/routes.php');

    require base_path('routes/admin/crm/sales_funnels/routes.php');

    require base_path('routes/admin/crm/kabba_ai_customers/routes.php');

});
