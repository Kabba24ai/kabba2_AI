<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\ViewController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\DeleteController;







Route::prefix('suppliers')
    ->name('suppliers.')
    ->group(function ($router) {

        Route::get('/', IndexController::class)->name('index');
    Route::post('/store', StoreController::class)->name('store');

    Route::get('/{id}', ViewController::class)->name('view');

    Route::delete('/delete/{supplier}', DeleteController::class)->name('delete');


    require base_path('routes/admin/maintenance_management/suppliers/category/routes.php');
    require base_path('routes/admin/maintenance_management/suppliers/tag/routes.php');
});
