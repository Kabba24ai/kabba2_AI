<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\SalesFunnels\IndexController;

use App\Http\Controllers\Admin\Crm\SalesFunnels\StoreController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\UpdateController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\DeleteController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\ShowController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\StatusController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\DuplicateController;

Route::prefix('sales-funnels')
    ->name('sales-funnels.')
    ->group(function () {
        require base_path('routes/admin/crm/sales_funnels/categories/routes.php');
        require base_path('routes/admin/crm/sales_funnels/steps/routes.php');

        Route::get('/', IndexController::class)->name('index');

        // Create funnel (AJAX)
        Route::post('/', StoreController::class)->name('store');

        // Update funnel
        Route::put('/{unique_id}', UpdateController::class)->name('update');

        // Delete funnel
        Route::delete('/{unique_id}', DeleteController::class)->name('delete');

        Route::get('/{unique_id}', ShowController::class)->name('show');

        Route::post('/{unique_id}/status', StatusController::class)->name('status.toggle');

        Route::post('/{unique_id}/duplicate', DuplicateController::class)->name('duplicate');
    });
