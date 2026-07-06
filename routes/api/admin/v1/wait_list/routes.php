<?php

use App\Http\Controllers\Api\Admin\V1\WaitList;
use Illuminate\Support\Facades\Route;

// Equipment Wait List — internal alert feed for the mobile app
Route::group(['prefix' => 'wait-list', 'as' => 'wait-list.'], function () {
    Route::get('/alerts', WaitList\AlertsIndexController::class)->name('alerts');
    Route::post('/alerts/{alert}/acknowledge', WaitList\AlertAcknowledgeController::class)->name('alerts.acknowledge');
});
