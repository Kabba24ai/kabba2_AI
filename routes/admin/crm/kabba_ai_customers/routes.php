<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\CreateController;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\DeleteController;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\EditController;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\IndexController;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\ShowController;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\StoreController;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\UpdateController;

Route::prefix('kabba-ai-customers')
    ->name('kabba-ai-customers.')
    ->middleware('kabba.ai.domain')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/', StoreController::class)->name('store');
        Route::get('/{uniqueId}/edit', EditController::class)->name('edit');
        Route::patch('/{uniqueId}', UpdateController::class)->name('update');
        Route::delete('/{uniqueId}', DeleteController::class)->name('destroy');
        Route::get('/{uniqueId}', ShowController::class)->name('show');
    });
