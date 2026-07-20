<?php

use Illuminate\Support\Facades\Route;

// Billing Charge Operations Commonization — the legacy Fuel Charge Alerts
// report (an AJAX tab on the Dash Reports page) is retired. Fuel charges
// are managed in the Fuel Charge Workspace; any stale bookmark or cached
// client hitting the old endpoint lands there. The route name is kept so
// nothing referencing it can break a deploy.
Route::prefix('fuel-charge-alerts')->name('fuel-charge-alerts.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.reports.fuel-charge-workspace.index'))->name('index');
});
