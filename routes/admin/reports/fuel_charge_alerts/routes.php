<?php

use App\Http\Controllers\Admin\Reports\FuelChargeAlerts\IndexController;

Route::prefix('fuel-charge-alerts')->name('fuel-charge-alerts.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
