<?php

use App\Http\Controllers\Admin\ServiceManagement\Approvals;
use App\Http\Controllers\Admin\ServiceManagement\ChargeLines;
use App\Http\Controllers\Admin\ServiceManagement\Deposit;
use App\Http\Controllers\Admin\ServiceManagement\DiagnosticSteps;
use App\Http\Controllers\Admin\ServiceManagement\Diagnostics;
use App\Http\Controllers\Admin\ServiceManagement\Notes;
use App\Http\Controllers\Admin\ServiceManagement\LaborEntries;
use App\Http\Controllers\Admin\ServiceManagement\Media;
use App\Http\Controllers\Admin\ServiceManagement\OverviewController;
use App\Http\Controllers\Admin\ServiceManagement\Parts;
use App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;
use App\Http\Controllers\Admin\ServiceManagement\RepairAuthorization;
use App\Http\Controllers\Admin\ServiceManagement\Settlement;
use App\Http\Controllers\Admin\ServiceManagement\Tickets;
use Illuminate\Support\Facades\Route;

Route::prefix('service-management')
    ->name('service-management.')
    ->group(function () {
        Route::get('/', OverviewController::class)->name('overview');

        // Equipment Reported-Problem templates (Equipment problem-templates)
        // — the taxonomy library, template builder, and per-unit attachment.
        // Built on the existing symptom library; no parallel problem_* tables.
        Route::prefix('problem-templates')->name('problem-templates.')->group(function () {
            // Template list + builder
            Route::get('/',                    [ProblemTemplates\TemplateController::class, 'index'])->name('index');
            Route::post('/',                   [ProblemTemplates\TemplateController::class, 'store'])->name('store');
            Route::get('/{profile}/builder',   [ProblemTemplates\TemplateController::class, 'builder'])->name('builder');
            Route::put('/{profile}',           [ProblemTemplates\TemplateController::class, 'update'])->name('update');
            Route::delete('/{profile}',        [ProblemTemplates\TemplateController::class, 'destroy'])->name('destroy');
            Route::post('/{profile}/items',    [ProblemTemplates\TemplateController::class, 'saveItems'])->name('save-items');

            // Library — Categories + Items taxonomy
            Route::get('/library',                 [ProblemTemplates\LibraryController::class, 'index'])->name('library');
            Route::post('/categories',             [ProblemTemplates\LibraryController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category}',   [ProblemTemplates\LibraryController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}',[ProblemTemplates\LibraryController::class, 'destroyCategory'])->name('categories.destroy');
            Route::post('/categories/reorder',     [ProblemTemplates\LibraryController::class, 'reorderCategories'])->name('categories.reorder');
            Route::post('/items',                  [ProblemTemplates\LibraryController::class, 'storeItem'])->name('items.store');
            Route::put('/items/{item}',            [ProblemTemplates\LibraryController::class, 'updateItem'])->name('items.update');
            Route::delete('/items/{item}',         [ProblemTemplates\LibraryController::class, 'destroyItem'])->name('items.destroy');
            Route::post('/items/reorder',          [ProblemTemplates\LibraryController::class, 'reorderItems'])->name('items.reorder');

            // Equipment attachment
            Route::get('/equipment',               [ProblemTemplates\EquipmentTemplateController::class, 'index'])->name('equipment');
            Route::post('/equipment/attach',       [ProblemTemplates\EquipmentTemplateController::class, 'attach'])->name('equipment.attach');
            Route::post('/equipment/bulk-apply',   [ProblemTemplates\EquipmentTemplateController::class, 'bulkApply'])->name('equipment.bulk-apply');
        });

        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                 Tickets\IndexController::class)->name('index');
            Route::get('/create',           Tickets\CreateController::class)->name('create');
            Route::post('/',                Tickets\StoreController::class)->name('store');
            Route::get('/{ticket}',         Tickets\ShowController::class)->name('show');
            Route::get('/{ticket}/edit',    Tickets\EditController::class)->name('edit');
            Route::put('/{ticket}',         Tickets\UpdateController::class)->name('update');
            Route::post('/{ticket}/status', Tickets\StatusController::class)->name('status');

            // Phase 2B — billing preparation (no payment processing)
            Route::post('/{ticket}/labor',                  LaborEntries\StoreController::class)->name('labor.store');
            Route::delete('/{ticket}/labor/{laborEntry}',   LaborEntries\DestroyController::class)->name('labor.destroy');
            Route::post('/{ticket}/charges',                ChargeLines\StoreController::class)->name('charges.store');
            Route::delete('/{ticket}/charges/{chargeLine}', ChargeLines\DestroyController::class)->name('charges.destroy');

            // Phase 4 — workbench: structured diagnostic steps + ticket notes
            Route::post('/{ticket}/diagnostic-steps',          DiagnosticSteps\StoreController::class)->name('diagnostic-steps.store');
            Route::delete('/{ticket}/diagnostic-steps/{step}', DiagnosticSteps\DestroyController::class)->name('diagnostic-steps.destroy');
            Route::post('/{ticket}/notes',                     Notes\StoreController::class)->name('notes.store');
            Route::put('/{ticket}/notes/{note}',               Notes\UpdateController::class)->name('notes.update');

            // Phase 2C — repair record (parts, media, timeline)
            Route::post('/{ticket}/parts',           Parts\StoreController::class)->name('parts.store');
            Route::delete('/{ticket}/parts/{part}',  Parts\DestroyController::class)->name('parts.destroy');
            Route::post('/{ticket}/media',           Media\StoreController::class)->name('media.store');
            Route::delete('/{ticket}/media/{media}', Media\DestroyController::class)->name('media.destroy');

            // Phase 2D — diagnostic-first lifecycle
            Route::post('/{ticket}/diagnostic/start',    Diagnostics\StartController::class)->name('diagnostic.start');
            Route::post('/{ticket}/diagnostic/complete', Diagnostics\CompleteController::class)->name('diagnostic.complete');
            Route::put('/{ticket}/diagnostic',           Diagnostics\UpdateController::class)->name('diagnostic.update');
            Route::post('/{ticket}/responsibility',      Diagnostics\DecideController::class)->name('responsibility.decide');

            // Phase 2E — approval & repair funding gate (state tracking only)
            Route::post('/{ticket}/approval/estimate-sent', Approvals\SendEstimateController::class)->name('approval.estimate-sent');
            Route::post('/{ticket}/approval/approve',       Approvals\ApproveController::class)->name('approval.approve');
            Route::post('/{ticket}/approval/decline',       Approvals\DeclineController::class)->name('approval.decline');
            Route::post('/{ticket}/approval/revoke',        Approvals\RevokeController::class)->name('approval.revoke');
            Route::post('/{ticket}/repair-authorization',        RepairAuthorization\StoreController::class)->name('authorization.store');
            Route::post('/{ticket}/repair-authorization/revoke',   RepairAuthorization\RevokeController::class)->name('authorization.revoke');
            Route::post('/{ticket}/repair-authorization/override', RepairAuthorization\OverrideController::class)->name('authorization.override');
            Route::put('/{ticket}/deposit',           Deposit\UpdateController::class)->name('deposit.update');
            Route::post('/{ticket}/deposit/override', Deposit\OverrideController::class)->name('deposit.override');

            // Phase 3A — customer settlement preview & handoff to the Financial Engine
            Route::get('/{ticket}/settlement',  Settlement\PreviewController::class)->name('settlement.preview');
            Route::post('/{ticket}/settlement', Settlement\StoreController::class)->name('settlement.store');
            Route::put('/{ticket}/labor/{laborEntry}',   LaborEntries\UpdateController::class)->name('labor.update');
            Route::put('/{ticket}/charges/{chargeLine}', ChargeLines\UpdateController::class)->name('charges.update');
            Route::put('/{ticket}/parts/{part}',         Parts\UpdateController::class)->name('parts.update');
        });
    });
