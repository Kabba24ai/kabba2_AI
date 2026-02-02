<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Applications\ApplicationStoreController;

Route::group(['prefix' => 'applications'], function () {
        
        Route::post('/store', ApplicationStoreController::class);
        
        
});
