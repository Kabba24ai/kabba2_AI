<?php

use App\Http\Controllers\Admin\Reports\NewDamageAlerts\IndexController;

Route::prefix('new-damage-alerts')->name('new-damage-alerts.')->group(function () {
    Route::get('/', IndexController::class)->name('index');
});
