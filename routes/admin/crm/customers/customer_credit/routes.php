<?php

use App\Http\Controllers\Admin\Crm\Customers\CustomerCredit\GrantController;
// Controllers
use App\Http\Controllers\Admin\Crm\Customers\CustomerCredit\RedeemController;
use Illuminate\Support\Facades\Route;

/**
 * Phase 3.1 — Customer Credit Administration.
 *
 * `permission:customer_credit.X` gates each route via Spatie's own
 * PermissionMiddleware — registered in bootstrap/app.php since before this
 * phase, but (per PHASE_3_1_ADMIN_AUDIT.md §4) never actually used by any
 * route in this codebase until now.
 */
Route::prefix('customer-credit')
    ->name('customer-credit.')
    ->group(function ($router) {
        Route::post('/grant', GrantController::class)
            ->name('grant')
            ->middleware('permission:customer_credit.grant');

        Route::post('/redeem', RedeemController::class)
            ->name('redeem')
            ->middleware('permission:customer_credit.redeem');
    });
