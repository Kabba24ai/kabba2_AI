<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\Auth\ResetPassword\IndexController as ResetPasswordIndexController;
use App\Http\Controllers\Front\Auth\ResetPassword\PostController as ResetPasswordPostController;
use App\Http\Controllers\Front\Auth\ResetPassword\SuccessController;

use App\Http\Controllers\Front\FrontendController;



Route::get('/', function () {
    return view('admin.auth.login.index');
});


// Frontend Routes
Route::get('/', [FrontendController::class, 'index'])->name('index');
Route::get('/faq', [FrontendController::class, 'faq'])->name('front.faq');
Route::get('/contact', [FrontendController::class, 'contact'])->name('front.contact');
Route::get('/category-listing/{slug}', [FrontendController::class, 'category_listing'])->name('front.category.listing');
Route::get('/category-child-listing/{slug}', [FrontendController::class, 'category_child_listing'])->name('front.category-child.listing');
Route::get('/product-details/{slug}', [FrontendController::class, 'product_details'])->name('front.product.details');

// login 

Route::get('/login', [FrontendController::class, 'login'])->name('front.login');




// Static Pages
Route::view('/privacy-policy', 'front.privacy-policy')->name('front.privacy');
Route::view('/terms-and-conditions', 'front.terms-and-conditions')->name('front.terms');

// Password Reset Flow
Route::get('/password/reset', ResetPasswordIndexController::class)->name('password.reset');
Route::post('/password/reset', ResetPasswordPostController::class)->name('password.update');
Route::get('/password/reset/success', SuccessController::class)->name('password.reset.success');
