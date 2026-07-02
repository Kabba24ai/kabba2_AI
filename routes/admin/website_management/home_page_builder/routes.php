<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\UpdateController;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Section;
use App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Item;

Route::prefix('home-builder')
    ->name('home-builder.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');

        Route::post('/update', UpdateController::class)->name('update');

        Route::prefix('section')->name('section.')->group(function () {
            Route::post('/sort', Section\SortController::class)->name('sort');
            Route::post('/{unique_id}/update', Section\UpdateController::class)->name('update');
            Route::delete('/{unique_id}/remove-image', Section\RemoveImageController::class)->name('remove-image');
        });

        Route::prefix('item')->name('item.')->group(function () {
            Route::post('/store', Item\StoreController::class)->name('store');
            Route::post('/{unique_id}/update', Item\UpdateController::class)->name('update');
            Route::delete('/{unique_id}', Item\DeleteController::class)->name('delete');
            Route::post('/sort', Item\SortController::class)->name('sort');
            Route::post('/{unique_id}/duplicate', Item\DuplicateController::class)->name('duplicate');
        });
    });
