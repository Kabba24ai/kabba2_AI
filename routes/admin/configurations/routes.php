<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Configurations\IndexController;
use App\Http\Controllers\Admin\Configurations\UpdateController;
use App\Http\Controllers\Admin\Configurations\VerifyMasterController;
use App\Http\Controllers\Admin\Configurations\SettingsResetController;




Route::prefix('configurations')
->name('configurations.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/', UpdateController::class);

    Route::post('/verify-master', VerifyMasterController::class)->name('verifyMaster');

    Route::post('/settings/reset', SettingsResetController::class)->name('settings.reset');
});
