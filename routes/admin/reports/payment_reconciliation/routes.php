<?php

use App\Http\Controllers\Admin\Reports\PaymentReconciliation\ExportController;
use App\Http\Controllers\Admin\Reports\PaymentReconciliation\IndexController;
use Illuminate\Support\Facades\Route;

// Authorize.Net Reconciliation — gateway vs Kabba, read-only. Independent of
// the Sales Summary / Sales Tax reports (those are untouched). POST is allowed
// so the optional Authorize.Net export file can be uploaded for cross-matching.
Route::prefix('authorize-net-reconciliation')
    ->name('authorize-net-reconciliation.')
    ->group(function () {
        Route::match(['get', 'post'], '/', IndexController::class)->name('index');
        Route::post('/export', ExportController::class)->name('export');
    });
