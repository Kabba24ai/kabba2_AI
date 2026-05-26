<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\MessageManagement\EmailTemplate\FetchController;

Route::prefix('email-template')
    ->name('email-template.')
    ->group(function () {
        Route::get('/category/{categoryId}', FetchController::class)->name('fetch');
    });
