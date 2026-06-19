<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary\IndexController;
use App\Http\Controllers\Admin\Reports\SalesReports\PureSalesSummary\ExportController;
use App\Http\Controllers\Admin\Reports\SalesReports\SalesTrend\IndexController as SalesTrendIndexController;
use App\Http\Controllers\Admin\Reports\SalesReports\SalesByStores\IndexController as SalesByStoresIndexController;

Route::prefix('sales-reports')
    ->name('sales-reports.')
    ->group(function () {

        Route::prefix('pure-sales-summary')
            ->name('pure-sales-summary.')
            ->group(function () {
                Route::get('/',       IndexController::class)->name('index');
                Route::get('/export', ExportController::class)->name('export');
            });

        Route::prefix('sales-trend')
            ->name('sales-trend.')
            ->group(function () {
                Route::get('/', SalesTrendIndexController::class)->name('index');
            });

        Route::prefix('sales-by-stores')
            ->name('sales-by-stores.')
            ->group(function () {
                Route::get('/', SalesByStoresIndexController::class)->name('index');
            });

    });
