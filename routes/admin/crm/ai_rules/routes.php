<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Crm\AiRules\IndexController;
use App\Http\Controllers\Admin\Crm\AiRules\StoreController;
use App\Http\Controllers\Admin\Crm\AiRules\UpdateController;
use App\Http\Controllers\Admin\Crm\AiRules\ApproveController;
use App\Http\Controllers\Admin\Crm\AiRules\DestroyController;

Route::prefix('ai-rules')
    ->name('ai-rules.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', StoreController::class)->name('store');
        Route::put('/{aiRule}', UpdateController::class)->name('update');
        Route::post('/{aiRule}/approve', ApproveController::class)->name('approve');
        Route::delete('/{aiRule}', DestroyController::class)->name('destroy');
    });
