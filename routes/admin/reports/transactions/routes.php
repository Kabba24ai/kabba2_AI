<?php

use App\Http\Controllers\Admin\Reports\Transactions\ExportController;
use App\Http\Controllers\Admin\Reports\Transactions\IndexController;
use Illuminate\Support\Facades\Route;

// Transaction Report — general financial listing of Kabba transactions.
// Read-only; independent of the Authorize.Net Reconciliation tool.
Route::prefix('transactions')
    ->name('transactions.')
    ->group(function () {
        Route::get('/',       IndexController::class)->name('index');
        Route::get('/export', ExportController::class)->name('export');
    });
