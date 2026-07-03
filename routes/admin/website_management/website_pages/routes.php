<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\CreateController;
use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\StoreController;
use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\EditController;
use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\UpdateController;
use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Section;
use App\Http\Controllers\Admin\WebsiteManagement\WebsitePages\Item;

Route::prefix('pages')->name('pages.')->group(function () {

    Route::get('/',                    IndexController::class)->name('index');
    Route::get('/create',              CreateController::class)->name('create');
    Route::post('/',                   StoreController::class)->name('store');
    Route::get('/{unique_id}/edit',    EditController::class)->name('edit');
    Route::post('/{unique_id}/update', UpdateController::class)->name('update');

    Route::prefix('section')->name('section.')->group(function () {
        Route::post('/store',                          Section\StoreController::class)->name('store');
        Route::post('/sort',                           Section\SortController::class)->name('sort');
        Route::post('/{unique_id}/update',             Section\UpdateController::class)->name('update');
        Route::delete('/{unique_id}/remove-image',     Section\RemoveImageController::class)->name('remove-image');
    });

    Route::prefix('item')->name('item.')->group(function () {
        Route::post('/store',                  Item\StoreController::class)->name('store');
        Route::post('/sort',                   Item\SortController::class)->name('sort');
        Route::post('/{unique_id}/update',     Item\UpdateController::class)->name('update');
        Route::delete('/{unique_id}',          Item\DeleteController::class)->name('delete');
        Route::post('/{unique_id}/duplicate',  Item\DuplicateController::class)->name('duplicate');
    });

});
