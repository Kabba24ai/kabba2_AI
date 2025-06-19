<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\TermsAndConditions;

// Static Pages

// Order Routes
Route::get('/privacy-policy', [TermsAndConditions::class, 'privacy_policy'])->name('front.privacy');
Route::get('/terms-and-conditions', [TermsAndConditions::class, 'terms_and_conditions'])->name('front.terms');
