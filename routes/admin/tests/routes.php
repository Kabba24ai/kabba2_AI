<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Tests\IndexController;

Route::prefix('test')->name('test.')
->group(function(){
    Route::get('/send-sms', [IndexController::class, 'sendSms'])->name('send_sms');
    Route::get('/send-whatsapp', [IndexController::class, 'sendWhatsApp'])->name('send_whatsapp');
    Route::get('/send-bulk-sms', [IndexController::class, 'sendBulkSms'])->name('send_bulk_sms');
});
