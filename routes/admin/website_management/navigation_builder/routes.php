<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\CreateController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\StoreController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\EditController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\UpdateController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\DestroyController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\TreeController;
use App\Http\Controllers\Admin\WebsiteManagement\NavigationBuilder\Item;

Route::prefix('navigation-builder')->name('navigation-builder.')->group(function () {

    Route::get('/',                        IndexController::class)->name('index');
    Route::get('/create',                  CreateController::class)->name('create');
    Route::post('/',                       StoreController::class)->name('store');
    Route::get('/{unique_id}/edit',        EditController::class)->name('edit');
    Route::post('/{unique_id}/update',     UpdateController::class)->name('update');
    Route::delete('/{unique_id}',          DestroyController::class)->name('destroy');
    Route::get('/{unique_id}/tree',        TreeController::class)->name('tree');

    Route::prefix('item')->name('item.')->group(function () {
        Route::post('/store',                       Item\StoreController::class)->name('store');
        Route::post('/sort',                        Item\SortController::class)->name('sort');
        Route::post('/{unique_id}/update',          Item\UpdateController::class)->name('update');
        Route::delete('/{unique_id}',               Item\DeleteController::class)->name('delete');
        Route::post('/{unique_id}/duplicate',       Item\DuplicateController::class)->name('duplicate');
        Route::post('/{unique_id}/toggle',          Item\ToggleController::class)->name('toggle');
    });

});
