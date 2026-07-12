<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\SiteContent\OpportunitiesSiteContentController;
use App\Http\Controllers\Api\EmploymentApplication\V1\SiteContent\OpportunitiesSiteContentUpdateController;

Route::group(['prefix' => 'site-content'], function () {

    // Public: the careers site reads active opportunity content.
    Route::get('/opportunities', OpportunitiesSiteContentController::class);

    // Internal: editing careers content requires an authenticated employee.
    Route::middleware(['auth:api_user'])->group(function () {
        Route::post('/opportunities/update/{id}', OpportunitiesSiteContentUpdateController::class);
    });
});