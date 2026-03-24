<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Crm\KabbaAiCustomers\IndexController;

Route::prefix('kabba-ai-customers')
    ->name('kabba-ai-customers.')
    ->middleware('kabba.ai.domain')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });
