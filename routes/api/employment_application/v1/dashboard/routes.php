<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\StatsController;

Route::group(['prefix' => 'dashboard'], function () {
        
        Route::get('/stats', StatsController::class);

        require base_path('routes/api/employment_application/v1/dashboard/applications/routes.php');
        require base_path('routes/api/employment_application/v1/dashboard/positions/routes.php');
        require base_path('routes/api/employment_application/v1/dashboard/opportunity_questions/routes.php');
});
