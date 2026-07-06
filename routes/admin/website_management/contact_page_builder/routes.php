<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\UpdateController;
use App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\Section;
use App\Http\Controllers\Admin\WebsiteManagement\ContactPageBuilder\Item;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Section as HPSection;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item as HPItem;

Route::prefix('contact-builder')
    ->name('contact-builder.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');
        Route::post('/update', UpdateController::class)->name('update');

        Route::prefix('section')->name('section.')->group(function () {
            Route::post('/sort', Section\SortController::class)->name('sort');
            Route::post('/{unique_id}/update', Section\UpdateController::class)->name('update');
            // Hero image removal reuses the shared helper; no cache concern for a media delete
            Route::delete('/{unique_id}/remove-image', HPSection\RemoveImageController::class)->name('remove-image');
        });

        Route::prefix('item')->name('item.')->group(function () {
            Route::post('/store', Item\StoreController::class)->name('store');
            Route::post('/{unique_id}/update', Item\UpdateController::class)->name('update');
            Route::delete('/{unique_id}', Item\DeleteController::class)->name('delete');
            Route::post('/sort', Item\SortController::class)->name('sort');
            // Duplicate not yet needed in contact builder; proxies to Home Builder handler
            Route::post('/{unique_id}/duplicate', HPItem\DuplicateController::class)->name('duplicate');
        });
    });
