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

        require base_path('routes/api/admin/v1/locations/routes.php');

        require base_path('routes/api/admin/v1/equipment/routes.php');

        require base_path('routes/api/admin/v1/customer_checklists/routes.php');

        require base_path('routes/api/admin/v1/rental_ready_checklists/routes.php');

        require base_path('routes/api/admin/v1/stores/routes.php');

        require base_path('routes/api/admin/v1/customers/routes.php');

        require base_path('routes/api/admin/v1/configurations/routes.php');
    });
});
