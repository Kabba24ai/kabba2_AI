<?php

use Illuminate\Support\Facades\Route;


/**
 *
 * Group: admin v1
 * Description: Routes For admin v1
 * Domain:
 *
 */


Route::group(['prefix' => 'v1'], function ($router) {
    // auth
    require base_path('routes/api/admin/v1/auth/routes.php');

    Route::middleware(['auth:api_user'])->group(function () {
        require base_path('routes/api/admin/v1/orders/routes.php');

        require base_path('routes/api/admin/v1/users/routes.php');

        require base_path('routes/api/admin/v1/products/routes.php');

        require base_path('routes/api/admin/v1/product_categories/routes.php');
    });
});
