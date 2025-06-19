<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\FrontendController;

Route::get('/', function () {
    return view('admin.auth.login.index');
});

// Frontend Routes
Route::get('/', [FrontendController::class, 'index'])->name('index');
Route::get('/faq', [FrontendController::class, 'faq'])->name('front.faq');
Route::get('/contact', [FrontendController::class, 'contact'])->name('front.contact');


