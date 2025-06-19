<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Front\CategoryController;


// Category Routes
Route::get('/category-listing/{slug}', [CategoryController::class, 'category_listing'])->name('front.category.listing');
Route::get('/category-child-listing/{slug}', [CategoryController::class, 'category_child_listing'])->name('front.category-child.listing');
