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


    Route::middleware(['auth', 'prevent-back-history'])->group(function ($router) {
        Route::get('logs', [LogViewerController::class, 'index']);

        // Dashboard
        require base_path('routes/admin/dashboard/routes.php');

        // Product Management
        require base_path('routes/admin/product_management/routes.php');

        // Order Management
        require base_path('routes/admin/order_management/routes.php');
    });
});
