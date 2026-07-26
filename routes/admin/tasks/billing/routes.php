<?php

use App\Http\Controllers\Admin\Tasks\Billing\OverviewController;
use App\Http\Controllers\Admin\Tasks\Billing\FuelCharges\IndexController as FuelChargesIndexController;
use App\Http\Controllers\Admin\Tasks\Billing\DamageCharges\IndexController as DamageChargesIndexController;
use App\Http\Controllers\Admin\Tasks\Billing\Settings\IndexController as BillingSettingsIndexController;
use App\Http\Controllers\Admin\Tasks\Billing\Settings\SaveController as BillingSettingsSaveController;
use Illuminate\Support\Facades\Route;

/*
 * Task Manager → Billing Operations.
 *
 * IMPORTANT: this file is required INSIDE the `tasks` group and BEFORE the
 * `/tasks/{task}` wildcard so the literal `billing` segment is never captured
 * as a Task model binding. Each workspace's single GET serves both the full
 * page and (with ?fragment=1) the queue + summary fragments for AJAX refresh.
 */
Route::prefix('billing')->name('billing.')->group(function () {

    // Billing Operations Overview — the entry point (/tasks/billing).
    Route::get('/', OverviewController::class)->name('index');

    // Fuel Charge Resolution (relocated from admin.reports.fuel-charge-workspace).
    Route::prefix('fuel-charges')->name('fuel-charges.')->group(function () {
        Route::get('/', FuelChargesIndexController::class)->name('index');
    });

    // Damage Charge Resolution (sibling activity workspace — separate queue).
    Route::prefix('damage-charges')->name('damage-charges.')->group(function () {
        Route::get('/', DamageChargesIndexController::class)->name('index');
    });

    // Billing Operations settings (Primary Billing Admin designation).
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', BillingSettingsIndexController::class)->name('index');
        Route::post('/', BillingSettingsSaveController::class)->name('save');
    });
});
