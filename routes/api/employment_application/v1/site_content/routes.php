<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\SiteContent\OpportunitiesSiteContentController;
use App\Http\Controllers\Api\EmploymentApplication\V1\SiteContent\OpportunitiesSiteContentUpdateController;

Route::group(['prefix' => 'site-content'], function () {

    Route::get('/opportunities', OpportunitiesSiteContentController::class);

      Route::post('/opportunities/update/{id}', OpportunitiesSiteContentUpdateController::class);
});