<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Tests\IndexController;

Route::prefix('test')->name('test.')
->group(function(){
    Route::get('/update-users-email', [IndexController::class, 'updateUsersEmail'])->name('update_users_email');
    Route::get('/send-sms', [IndexController::class, 'sendSms'])->name('send_sms');
    Route::get('/send-whatsapp', [IndexController::class, 'sendWhatsApp'])->name('send_whatsapp');
    Route::get('/send-bulk-sms', [IndexController::class, 'sendBulkSms'])->name('send_bulk_sms');
    Route::get('/send-same-day-job', [IndexController::class, 'sendSameDayJob'])->name('send_same_day_job');
    Route::get('/send-day-before-job', [IndexController::class, 'sendDayBeforeJob'])->name('send_day_before_job');
});
