<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Configurations\IndexController;
use App\Http\Controllers\Admin\Configurations\UpdateController;
use App\Http\Controllers\Admin\Configurations\VerifyMasterController;
use App\Http\Controllers\Admin\Configurations\SettingsResetController;
use App\Http\Controllers\Admin\Configurations\New\IndexController as NewIndexController;
use App\Http\Controllers\Admin\Configurations\New\ContactUsSettings\SaveController as SaveContactUsSettingsController;
use App\Http\Controllers\Admin\Configurations\New\ProductSettings\SaveController as ProductSettingsSaveController;


Route::prefix('configurations')
->name('configurations.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/', UpdateController::class);

    Route::post('/verify-master', VerifyMasterController::class)->name('verifyMaster');

    Route::post('/settings/reset', SettingsResetController::class)->name('settings.reset');

    Route::prefix('new')->name('new.')->group(function ($router) {
        Route::get('/', NewIndexController::class)->name('index');
        Route::post('/contact-us-settings', SaveContactUsSettingsController::class)->name('save-contact-us-settings');
        Route::post('/product-settings', ProductSettingsSaveController::class)->name('save-product-settings');
    });
});
