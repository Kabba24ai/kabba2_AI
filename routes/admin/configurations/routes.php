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
use App\Http\Controllers\Admin\Configurations\ProfileSettings\SaveController as SaveProfileSettingsController;

use App\Http\Controllers\Admin\Configurations\NotificationSettings\HrmUsersController;
use App\Http\Controllers\Admin\Configurations\NotificationSettings\SaveController as SaveNotificationSettingsController;
use App\Http\Controllers\Admin\Configurations\NotificationSettings\SaveManualController as SaveManualNotificationController;

use App\Http\Controllers\Admin\Configurations\ApplicationCodeSettings\SaveController as SaveApplicationCodeController;
use App\Http\Controllers\Admin\Configurations\TimezoneSettings\SaveController as SaveTimezoneSettingsController;


use App\Http\Controllers\Admin\Configurations\NotificationSettings\DeleteController as DeleteNotificationSettingsController;




Route::prefix('configurations')
->name('configurations.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/product-settings', SaveProductSettingsController::class)->name('save-product-settings');
    Route::post('/contact-us-settings', SaveContactUsSettingsController::class)->name('save-contact-us-settings');
    Route::post('/company-settings', \App\Http\Controllers\Admin\Configurations\CompanySettings\SaveController::class)->name('save-company-settings');
    Route::post('/communication-settings', SaveCommunicationSettingsController::class)->name('save-communication-settings');
    Route::post('/admin-settings', SaveAdminSettingsController::class)->name('save-admin-settings');
    Route::post('/mail-send-settings', SaveMailSendController::class)->name('save-mail-send-settings');
    Route::post('/payment-integration-settings', SavePaymentIntegrationController::class)->name('save-payment-integration-settings');
    Route::post('/social-media-settings', SaveSocialMediaController::class)->name('save-social-media-settings');
    Route::post('/invoice-settings', SaveInvoiceSettingsController::class)->name('save-invoice-settings');
    Route::post('/price-settings', SavePriceSettingsController::class)->name('save-price-settings');
    Route::post('/profile-settings', SaveProfileSettingsController::class)->name('save-profile-settings');
Route::post('/application-code-settings', SaveApplicationCodeController::class)
    ->name('save-application-code-settings');
    Route::post('/timezone-settings', SaveTimezoneSettingsController::class)->name('save-timezone-settings');

    // Google Maps & Routing — global platform integration (shared by Field
    // Service now, Dispatch later). Save + net-new Test Connection diagnostic.
    Route::post('/google-maps-settings', \App\Http\Controllers\Admin\Configurations\GoogleMaps\SaveController::class)->name('save-google-maps-settings');
    Route::post('/google-maps-test-connection', \App\Http\Controllers\Admin\Configurations\GoogleMaps\TestConnectionController::class)->name('google-maps-test-connection');

    Route::post('/verify-master', VerifyMasterController::class)->name('verify-master');
    Route::post('/settings/reset', SettingsResetController::class)->name('reset-settings');

    Route::post('/terms-and-conditions', SaveTermsController::class)->name('save-terms-and-conditions');
    Route::post('/privacy-policy', SavePrivacyPolicyController::class)->name('save-privacy-policy');

    // Route::prefix('legacy')->name('legacy.')->group(function ($router) {
    //     Route::get('/', LegacyIndexController::class)->name('index');
    //     Route::post('/', UpdateController::class);
    // });

    Route::get('/hrm-users', HrmUsersController::class)->name('hrm-users.index');

    Route::post('/notification-settings.save', SaveNotificationSettingsController::class)->name('notification-settings.save');
    Route::post('/notification-settings.manual.store', SaveManualNotificationController::class)->name('notification-settings.manual.store');


    Route::delete('notification-settings-delete/{notification}',DeleteNotificationSettingsController::class)->name('notification-settings.delete');

    // Note Presets administration (Billing Engine UI refinement) — the
    // managed lists behind the fuel-note / resolution-note dropdowns.
    Route::get('/note-presets', [\App\Http\Controllers\Admin\Configurations\NotePresetsController::class, 'index'])->name('note-presets.index');
    Route::get('/note-presets/{type}/list', [\App\Http\Controllers\Admin\Configurations\NotePresetsController::class, 'list'])->name('note-presets.list');
    Route::post('/note-presets/{type}/reorder', [\App\Http\Controllers\Admin\Configurations\NotePresetsController::class, 'reorder'])->name('note-presets.reorder');
    Route::post('/note-presets/{type}', [\App\Http\Controllers\Admin\Configurations\NotePresetsController::class, 'store'])->name('note-presets.store');
    Route::put('/note-presets/{type}/{id}', [\App\Http\Controllers\Admin\Configurations\NotePresetsController::class, 'update'])->name('note-presets.update');
    Route::delete('/note-presets/{type}/{id}', [\App\Http\Controllers\Admin\Configurations\NotePresetsController::class, 'destroy'])->name('note-presets.destroy');

});
