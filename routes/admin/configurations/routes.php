<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Configurations\Legacy\IndexController as LegacyIndexController;
use App\Http\Controllers\Admin\Configurations\Legacy\UpdateController;

use App\Http\Controllers\Admin\Configurations\IndexController;
use App\Http\Controllers\Admin\Configurations\VerifyMasterController;
use App\Http\Controllers\Admin\Configurations\SettingsResetController;
use App\Http\Controllers\Admin\Configurations\ProductSettings\SaveController as SaveProductSettingsController;
use App\Http\Controllers\Admin\Configurations\ContactUsSettings\SaveController as SaveContactUsSettingsController;
use App\Http\Controllers\Admin\Configurations\CommunicationSettings\SaveController as SaveCommunicationSettingsController;
use App\Http\Controllers\Admin\Configurations\AdminSettings\SaveController as SaveAdminSettingsController;
use App\Http\Controllers\Admin\Configurations\MailSendSettings\SaveController as SaveMailSendController;
use App\Http\Controllers\Admin\Configurations\PaymentIntegration\SaveController as SavePaymentIntegrationController;
use App\Http\Controllers\Admin\Configurations\SocialMediaIntegration\SaveController as SaveSocialMediaController;
use App\Http\Controllers\Admin\Configurations\InvoiceSettings\SaveController as SaveInvoiceSettingsController;
use App\Http\Controllers\Admin\Configurations\PriceSettings\SaveController as SavePriceSettingsController;
use App\Http\Controllers\Admin\Configurations\TermsConditions\SaveController as SaveTermsController;
use App\Http\Controllers\Admin\Configurations\PrivacyPolicy\SaveController as SavePrivacyPolicyController;


Route::prefix('configurations')
->name('configurations.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/product-settings', SaveProductSettingsController::class)->name('save-product-settings');
    Route::post('/contact-us-settings', SaveContactUsSettingsController::class)->name('save-contact-us-settings');
    Route::post('/communication-settings', SaveCommunicationSettingsController::class)->name('save-communication-settings');
    Route::post('/admin-settings', SaveAdminSettingsController::class)->name('save-admin-settings');
    Route::post('/mail-send-settings', SaveMailSendController::class)->name('save-mail-send-settings');
    Route::post('/payment-integration-settings', SavePaymentIntegrationController::class)->name('save-payment-integration-settings');
    Route::post('/social-media-settings', SaveSocialMediaController::class)->name('save-social-media-settings');
    Route::post('/invoice-settings', SaveInvoiceSettingsController::class)->name('save-invoice-settings');
    Route::post('/price-settings', SavePriceSettingsController::class)->name('save-price-settings');

    Route::post('/verify-master', VerifyMasterController::class)->name('verify-master');
    Route::post('/settings/reset', SettingsResetController::class)->name('reset-settings');

    Route::post('/terms-and-conditions', SaveTermsController::class)->name('save-terms-and-conditions');
    Route::post('/privacy-policy', SavePrivacyPolicyController::class)->name('save-privacy-policy');

    // Route::prefix('legacy')->name('legacy.')->group(function ($router) {
    //     Route::get('/', LegacyIndexController::class)->name('index');
    //     Route::post('/', UpdateController::class);
    // });
});
