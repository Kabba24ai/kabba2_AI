<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Configurations\IndexController;
use App\Http\Controllers\Admin\Configurations\UpdateController;
use App\Http\Controllers\Admin\Configurations\VerifyMasterController;
use App\Http\Controllers\Admin\Configurations\SettingsResetController;
use App\Http\Controllers\Admin\Configurations\New\IndexController as NewIndexController;
use App\Http\Controllers\Admin\Configurations\New\ContactUsSettings\SaveController;
use App\Http\Controllers\Admin\Configurations\New\ProductSettings\SaveRateController;


Route::prefix('configurations')
->name('configurations.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/', UpdateController::class);

    Route::post('/verify-master', VerifyMasterController::class)->name('verifyMaster');

    Route::post('/settings/reset', SettingsResetController::class)->name('settings.reset');

    Route::prefix('new')->name('new.')->group(function ($router) {
        Route::get('/', NewIndexController::class)->name('index');
        Route::post('/contact-us-settings', SaveController::class)->name('save-contact-us-settings');
        Route::post('/product-rate-settings', SaveRateController::class)->name('save-product-rate');
    });
});
