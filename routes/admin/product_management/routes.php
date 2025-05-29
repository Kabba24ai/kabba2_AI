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

Route::prefix('product-management')
->name('product-management.')
->group(function ($router) {

    // Categories
    require base_path('routes/admin/product_management/categories/routes.php');

    // Products
    require base_path('routes/admin/product_management/products/routes.php');
});
