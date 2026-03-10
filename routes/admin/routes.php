<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Schema;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

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

Route::name('admin.')->group(function ($router) {
    // Auth
    require base_path('routes/admin/auth/routes.php');


    Route::middleware(['auth', 'prevent-back-history', 'admin.common-data'])->group(function ($router) {
        Route::get('logs', [LogViewerController::class, 'index']);

        // Media
        require base_path('routes/admin/media/routes.php');

        // Test Route
        require base_path('routes/admin/tests/routes.php');

        // Dashboard
        require base_path('routes/admin/dashboard/routes.php');

        // Product Management
        require base_path('routes/admin/product_management/routes.php');

        // Terms and Condition
        require base_path('routes/admin/terms_and_conditions/routes.php');

        // Order Management
        require base_path('routes/admin/order_management/routes.php');

        // Configurations
        require base_path('routes/admin/configurations/routes.php');

        // Maintenance Management
        require base_path('routes/admin/maintenance_management/routes.php');



        // crm
        require base_path('routes/admin/crm/routes.php');

        // reports
        require base_path('routes/admin/reports/routes.php');

        // checklist
        require base_path('routes/admin/checklist_management/routes.php');

        // Stores
        require base_path('routes/admin/stores/routes.php');

        // hrm
        require base_path('routes/admin/hrm/routes.php');


        // website_management
        require base_path('routes/admin/website_management/routes.php');

    });
});
