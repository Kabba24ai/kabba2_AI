<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Tests\IndexController;

Route::prefix('test')->name('test.')
->group(function(){

    Route::get('/sales-funnel-before-event-job', [IndexController::class, 'salesFunnelBeforeEventJob'])->name('sales_funnel_before_event_job');
    Route::get('/sales-funnel-after-event-job', [IndexController::class, 'salesFunnelAfterEventJob'])->name('sales_funnel_after_event_job');

    Route::get('/sales-funnel-after-event', [IndexController::class, 'salesFunnelAfterEvent'])->name('sales_funnel_after_event');
    Route::get('/sales-funnel-before-event', [IndexController::class, 'salesFunnelBeforeEvent'])->name('sales_funnel_before_event');

    Route::get('/equipment-list/{type?}', [IndexController::class, 'equipmentList'])->name('equipment_list');
    Route::get('/send-custom-firebase-notification/{token}', [IndexController::class, 'sendCustomFirebaseNotification'])->name('send_custom_firebase_notification');
    Route::get('/send-firebase-notification', [IndexController::class, 'sendFirebaseNotification'])->name('send_firebase_notification');
    Route::get('/list-timezones', [IndexController::class, 'listTimezonesAndCurrentTimes'])->name('list_timezones');
    Route::get('/send-cod-sms', [IndexController::class, 'testSendCodSms'])->name('send_cod_sms');
    Route::get('/update-users-email', [IndexController::class, 'updateUsersEmail'])->name('update_users_email');
    Route::get('/send-sms', [IndexController::class, 'sendSms'])->name('send_sms');
    Route::get('/send-whatsapp', [IndexController::class, 'sendWhatsApp'])->name('send_whatsapp');
    Route::get('/send-bulk-sms', [IndexController::class, 'sendBulkSms'])->name('send_bulk_sms');
    Route::get('/send-same-day-job', [IndexController::class, 'sendSameDayJob'])->name('send_same_day_job');
    Route::get('/send-day-before-job', [IndexController::class, 'sendDayBeforeJob'])->name('send_day_before_job');
});
