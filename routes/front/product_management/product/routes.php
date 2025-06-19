<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\ProductController;

// Product Routes
Route::get('/product-details/{slug}', [ProductController::class, 'product_details'])->name('front.product.details');
