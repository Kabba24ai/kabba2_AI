<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\MessageManagement\Audiences;
use App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastQueue;
use App\Http\Controllers\Admin\Crm\MessageManagement\BroadcastWizard;

// ── Broadcast Creation Wizard ──────────────────────────────────────
Route::prefix('broadcast-wizard')
    ->name('broadcast-wizard.')
    ->group(function () {
        Route::get('/', BroadcastWizard\CreateController::class)->name('create');
        Route::post('/draft', BroadcastWizard\SaveDraftController::class)->name('draft');
        Route::get('/preview-audience', BroadcastWizard\PreviewAudienceController::class)->name('preview-audience');
        Route::post('/{id}/queue', BroadcastWizard\QueueController::class)->name('queue');
    });

// ── Saved Audiences ────────────────────────────────────────────────
Route::prefix('audiences')
    ->name('audiences.')
    ->group(function () {
        Route::post('/store', Audiences\StoreController::class)->name('store');
    });

// ── Broadcast Queue / History ──────────────────────────────────────
Route::prefix('broadcast-queue')
    ->name('broadcast-queue.')
    ->group(function () {
        Route::get('/', BroadcastQueue\IndexController::class)->name('index');
        Route::get('/{id}', BroadcastQueue\ShowController::class)->name('show')->whereNumber('id');
        Route::get('/{id}/send', BroadcastQueue\SendWizardController::class)->name('send');
        Route::post('/{id}/authorize', BroadcastQueue\AuthorizeSendController::class)->name('authorize');
        Route::post('/{id}/reschedule', BroadcastQueue\RescheduleController::class)->name('reschedule');
        Route::post('/{id}/cancel', BroadcastQueue\CancelController::class)->name('cancel');
        Route::post('/{id}/archive', BroadcastQueue\ArchiveController::class)->name('archive');
        Route::post('/{id}/rebuild', BroadcastQueue\RebuildController::class)->name('rebuild');
        Route::delete('/{id}', BroadcastQueue\DeleteController::class)->name('delete');
    });
