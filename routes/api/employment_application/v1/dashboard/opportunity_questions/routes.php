<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\QuestionListController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\QuestionReorderController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\QuestionStatusController;

use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\StoreQuestionController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\UpdateQuestionController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions\DeleteQuestionController;



Route::group(['prefix' => 'opportunity-questions'], function () {

   Route::get('/list', QuestionListController::class);

   Route::post('/reorder', QuestionReorderController::class);

   Route::post('/{uniqueId}/status',QuestionStatusController::class);

   Route::put('/{uniqueId}',UpdateQuestionController::class);

   Route::post('/store',StoreQuestionController::class);

   Route::delete('/{uniqueId}', DeleteQuestionController::class);

});
