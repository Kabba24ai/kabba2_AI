<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\Users\FetchUserController;
use App\Http\Controllers\Api\TimeTracker\V1\Users\ListUsersController;
use App\Http\Controllers\Api\TimeTracker\V1\Users\VacationOptionController;
use App\Http\Controllers\Api\TimeTracker\V1\Users\UpdateUserVacationController;

use App\Http\Controllers\Api\TimeTracker\V1\Users\GetAdminAttendanceSummaryController;

use App\Http\Controllers\Api\TimeTracker\V1\Users\TimeEntryListController;
use App\Http\Controllers\Api\TimeTracker\V1\Users\ExportEmployeeTimeEntriesController;

use App\Http\Controllers\Api\TimeTracker\V1\Users\TimeEntryUpdateController;
use App\Http\Controllers\Api\TimeTracker\V1\Users\TimeEntryCreateController;
use App\Http\Controllers\Api\TimeTracker\V1\Users\CreateEmptyTimeEntryController;

use App\Http\Controllers\Api\TimeTracker\V1\Users\TimeEntryBulkUpdateController;


Route::group(['prefix' => 'users'], function () {

        // LIST employees
        Route::get('/', ListUsersController::class);

        Route::get('/get/{user}', FetchUserController::class);

        Route::get('/vacation-Option', VacationOptionController::class);

            //  UPDATE vacation settings
        Route::put('/{user}/vacation', UpdateUserVacationController::class);

        Route::get('/attendance/summary', GetAdminAttendanceSummaryController::class);

        Route::get('/time-entries-list/{user}', TimeEntryListController::class);

        Route::get('/{user}/time-entries/export', ExportEmployeeTimeEntriesController::class);

        Route::post('/time-entries/update', TimeEntryUpdateController::class);

        Route::post('/time-entries/create', TimeEntryCreateController::class);

        Route::post('/time-entries/create-empty', CreateEmptyTimeEntryController::class);

        Route::post('/time-entries/bulk-update', TimeEntryBulkUpdateController::class);
});
