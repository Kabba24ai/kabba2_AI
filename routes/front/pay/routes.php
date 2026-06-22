<?php

use App\Http\Controllers\Front\Checkout\PaymentShortLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/pay/{token}', PaymentShortLinkController::class)
    ->name('pay.redirect')
    ->where('token', '[A-Za-z0-9]{6,16}');
