<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch\IndexController as DispatchLogicIndex;
use App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch\StoreController as DispatchLogicStore;
use App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch\UpdateController as DispatchLogicUpdate;
use App\Http\Controllers\Admin\Tutorials\SystemLogic\Dispatch\DestroyController as DispatchLogicDestroy;

Route::prefix('tutorials')
    ->name('tutorials.')
    ->group(function () {

        // System Logic — Dispatch
        Route::prefix('system-logic/dispatch')
            ->name('system-logic.dispatch.')
            ->group(function () {
                Route::get('/',          DispatchLogicIndex::class)->name('index');
                Route::post('/',         DispatchLogicStore::class)->name('store');
                Route::put('/{id}',      DispatchLogicUpdate::class)->name('update');
                Route::delete('/{id}',   DispatchLogicDestroy::class)->name('destroy');
            });

    });
