<?php

use App\Http\Controllers\Admin\OrderManagement\QueueLine\IndexController;
use App\Http\Controllers\Admin\OrderManagement\QueueLine\WallBoardController;
use Illuminate\Support\Facades\Route;

// Access gated by the queue_line.view ability (Spatie permission middleware,
// same pattern as wait_list). Under the current global Gate::before bypass
// every signed-in admin passes; guests never do. When granular enforcement
// returns, the ModuleSeeder-seeded queue_line.view/manage abilities become
// live without route changes.
Route::prefix('queue-line')
    ->name('queue-line.')
    ->middleware('permission:queue_line.view')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/wall', WallBoardController::class)->name('wallboard');
    });
