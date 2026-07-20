<?php

use App\Http\Controllers\Admin\Reports\FuelChargeWorkspace\IndexController;
use Illuminate\Support\Facades\Route;

// Dashboard V2 Phase 1A — the Fuel Charge operational workspace. The one
// GET endpoint serves both the full page and (with ?fragment=1) the
// queue + summary fragments for AJAX refresh after filters/actions.
Route::prefix('fuel-charge-workspace')->name('fuel-charge-workspace.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
