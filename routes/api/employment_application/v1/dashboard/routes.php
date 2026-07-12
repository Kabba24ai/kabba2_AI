<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\StatsController;

// Internal employee dashboard — the entire subtree (applicant list/details/
// status, position CRUD, opportunity-question CRUD, stats) requires an
// authenticated employee. Authentication only: any authenticated internal
// user may access, no role/permission or store restriction (by design).
// Anonymous/invalid-token requests receive a JSON 401 (ForceJsonResponse
// makes the guard render JSON, never an HTML login redirect). The public
// applicant surface (apply, positions, reference data, careers content) and
// the SSO token-exchange login stay outside this group and remain public.
Route::group(['prefix' => 'dashboard', 'middleware' => ['auth:api_user']], function () {

        Route::get('/stats', StatsController::class);

        require base_path('routes/api/employment_application/v1/dashboard/applications/routes.php');
        require base_path('routes/api/employment_application/v1/dashboard/positions/routes.php');
        require base_path('routes/api/employment_application/v1/dashboard/opportunity_questions/routes.php');
});
