<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary\IndexController;
use App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary\ExportController;

Route::prefix('sales-reports')
    ->name('sales-reports.')
    ->group(function () {

        Route::prefix('pure-sales-summary')
            ->name('pure-sales-summary.')
            ->group(function () {
                Route::get('/',       IndexController::class)->name('index');
                Route::get('/export', ExportController::class)->name('export');
            });

    });
