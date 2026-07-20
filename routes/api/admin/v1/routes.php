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
    // client
    require base_path('routes/api/admin/v1/clients/routes.php');

    // auth
    require base_path('routes/api/admin/v1/auth/routes.php');

    require base_path('routes/api/admin/v1/authorize/routes.php');

    Route::middleware(['auth:api_user'])->group(function () {
        require base_path('routes/api/admin/v1/orders/routes.php');

        require base_path('routes/api/admin/v1/users/routes.php');

        require base_path('routes/api/admin/v1/wait_list/routes.php');

        require base_path('routes/api/admin/v1/hrm/routes.php');

        require base_path('routes/api/admin/v1/products/routes.php');

        require base_path('routes/api/admin/v1/product_categories/routes.php');

        require base_path('routes/api/admin/v1/locations/routes.php');

        require base_path('routes/api/admin/v1/equipment/routes.php');

        require base_path('routes/api/admin/v1/customer_checklists/routes.php');

        require base_path('routes/api/admin/v1/rental_ready_checklists/routes.php');

        require base_path('routes/api/admin/v1/stores/routes.php');

        require base_path('routes/api/admin/v1/customers/routes.php');

        require base_path('routes/api/admin/v1/configurations/routes.php');

        require base_path('routes/api/admin/v1/user_notification/routes.php');

        require base_path('routes/api/admin/v1/dispatch/routes.php');

        // queue line — standalone yard staging module (board / switch / fuel / history)
        require base_path('routes/api/admin/v1/queue_line/routes.php');

        require base_path('routes/api/admin/v1/tasks/routes.php');
    });
});
