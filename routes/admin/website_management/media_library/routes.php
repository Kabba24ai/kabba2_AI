<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\UploadController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\PickerController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\ShowController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\UpdateController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\DestroyController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\ReplaceController;
use App\Http\Controllers\Admin\WebsiteManagement\MediaLibrary\Folder\StoreController as FolderStoreController;

Route::prefix('media-library')->name('media-library.')->group(function () {

    Route::get('/',                          IndexController::class)->name('index');
    Route::post('/upload',                   UploadController::class)->name('upload');
    Route::get('/picker',                    PickerController::class)->name('picker');

    Route::get('/{unique_id}',               ShowController::class)->name('show');
    Route::patch('/{unique_id}',             UpdateController::class)->name('update');
    Route::delete('/{unique_id}',            DestroyController::class)->name('destroy');
    Route::post('/{unique_id}/replace',      ReplaceController::class)->name('replace');

    Route::prefix('folders')->name('folders.')->group(function () {
        Route::post('/',                     FolderStoreController::class)->name('store');
    });

});
