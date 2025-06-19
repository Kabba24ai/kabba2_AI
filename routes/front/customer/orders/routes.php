<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\OrderController;

// Order Routes
Route::get('/customer-account', [OrderController::class, 'customer_account'])->name('account');
