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

// Quick-create controllers (inline "Add New" from the step modal)
use App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate\SmsCategoryController   as QcSmsCategoryController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate\SmsTemplateController   as QcSmsTemplateController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate\EmailCategoryController as QcEmailCategoryController;
use App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate\EmailTemplateController as QcEmailTemplateController;

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

        // ── Inline quick-create endpoints (used by the "Add New" panels in the step modal) ──
        Route::prefix('quick-create')->name('quick-create.')->group(function () {
            Route::post('/sms-category',   QcSmsCategoryController::class)->name('sms-category');
            Route::post('/sms-template',   QcSmsTemplateController::class)->name('sms-template');
            Route::post('/email-category', QcEmailCategoryController::class)->name('email-category');
            Route::post('/email-template', QcEmailTemplateController::class)->name('email-template');
        });
    });
