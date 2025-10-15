<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\StoreController;





Route::prefix('suppliers')
    ->name('suppliers.')
    ->group(function ($router) {

        Route::get('/', IndexController::class)->name('index');
    Route::post('/store', StoreController::class)->name('store');

    require base_path('routes/admin/maintenance_management/suppliers/category/routes.php');
    require base_path('routes/admin/maintenance_management/suppliers/tag/routes.php');
});
