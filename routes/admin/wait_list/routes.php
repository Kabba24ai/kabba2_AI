<?php

use App\Http\Controllers\Admin\WaitList;
use Illuminate\Support\Facades\Route;

// Equipment Wait List — dedicated operational module. Access is gated by
// the wait_list.view ability (Spatie permission middleware, same pattern as
// customer_credit / resolution_center); the dashboard Wait List
// Opportunities card checks the SAME ability. Under the current global
// Gate::before bypass every signed-in user passes; guests never do.
Route::prefix('wait-list')
    ->name('wait-list.')
    ->middleware('permission:wait_list.view')
    ->group(function () {
        Route::get('/',        WaitList\IndexController::class)->name('index');
        Route::get('/create',  WaitList\CreateController::class)->name('create');
        Route::post('/',       WaitList\StoreController::class)->name('store');
        Route::get('/alerts',  WaitList\AlertsController::class)->name('alerts');
        Route::get('/{waitList}', WaitList\ShowController::class)->name('show');

        Route::post('/{waitList}/communications', WaitList\CommunicationStoreController::class)->name('communications.store');
        Route::post('/{waitList}/convert',        WaitList\ConvertController::class)->name('convert');
        Route::post('/{waitList}/cancel',         WaitList\CancelController::class)->name('cancel');

        Route::post('/alerts/{alert}/acknowledge',  WaitList\AlertAcknowledgeController::class)->name('alerts.acknowledge');
        Route::post('/alerts/{alert}/dismiss',      WaitList\AlertDismissController::class)->name('alerts.dismiss');
        Route::post('/alerts/{alert}/disposition',  WaitList\AlertDispositionController::class)->name('alerts.disposition');
    });
