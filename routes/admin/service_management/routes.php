<?php

use App\Http\Controllers\Admin\ServiceManagement\Approvals;
use App\Http\Controllers\Admin\ServiceManagement\ChargeLines;
use App\Http\Controllers\Admin\ServiceManagement\CustomerDamage;
use App\Http\Controllers\Admin\ServiceManagement\Deposit;
use App\Http\Controllers\Admin\ServiceManagement\DiagnosticSteps;
use App\Http\Controllers\Admin\ServiceManagement\Diagnostics;
use App\Http\Controllers\Admin\ServiceManagement\Notes;
use App\Http\Controllers\Admin\ServiceManagement\LaborEntries;
use App\Http\Controllers\Admin\ServiceManagement\Media;
use App\Http\Controllers\Admin\ServiceManagement\BoardController;
use App\Http\Controllers\Admin\ServiceManagement\Parts;
use App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;
use App\Http\Controllers\Admin\ServiceManagement\RepairAuthorization;
use App\Http\Controllers\Admin\ServiceManagement\Settlement;
use App\Http\Controllers\Admin\ServiceManagement\Tickets;
use Illuminate\Support\Facades\Route;

// ST-3 permission annotations. These are INERT today — AppServiceProvider's
// global Gate::before bypass passes every signed-in user — but make the
// module correctly gated the moment granular enforcement is switched back
// on (declared in Iam\ModuleSeeder). view = read; manage = ticket work;
// authorize = approvals/repair-authorization/overrides/deposit; bill =
// settlement charge creation. problem_templates has its own view/manage.
Route::prefix('service-management')
    ->name('service-management.')
    ->group(function () {
        // Service Operations Board — THE canonical Service entry point, launch
        // surface, and return destination for all service work. Lives at the
        // module root; the legacy Overview page has been removed.
        Route::get('/', BoardController::class)->name('board')->middleware('permission:service_tickets.view');

        // Customer Damage Staging — the review bridge between the return
        // checklist and disposition (No Action / Charge / Service Ticket).
        // INERT permission annotations (Gate::before bypass) like the rest of
        // the module: view = read the queue; manage = create/dispose.
        Route::prefix('customer-damage')->name('customer-damage.')
            ->middleware('permission:customer_damage.view')
            ->group(function () {
            Route::get('/',                        CustomerDamage\IndexController::class)->name('index');
            Route::get('/order-products',          CustomerDamage\OrderProductLookupController::class)->name('order-products');
            Route::post('/',                       CustomerDamage\StoreController::class)->name('store')->middleware('permission:customer_damage.manage');
            Route::post('/{uniqueId}/disposition', CustomerDamage\DispositionController::class)->name('disposition')->middleware('permission:customer_damage.manage');
        });

        // Equipment Reported-Problem templates (Equipment problem-templates)
        // — the taxonomy library, template builder, and per-unit attachment.
        // Built on the existing symptom library; no parallel problem_* tables.
        Route::prefix('problem-templates')->name('problem-templates.')
            ->middleware('permission:problem_templates.view')
            ->group(function () {
            // Template list + builder
            Route::get('/',                    [ProblemTemplates\TemplateController::class, 'index'])->name('index');
            Route::post('/',                   [ProblemTemplates\TemplateController::class, 'store'])->name('store')->middleware('permission:problem_templates.manage');
            Route::get('/{profile}/builder',   [ProblemTemplates\TemplateController::class, 'builder'])->name('builder');
            Route::put('/{profile}',           [ProblemTemplates\TemplateController::class, 'update'])->name('update')->middleware('permission:problem_templates.manage');
            Route::delete('/{profile}',        [ProblemTemplates\TemplateController::class, 'destroy'])->name('destroy')->middleware('permission:problem_templates.manage');
            Route::post('/{profile}/items',    [ProblemTemplates\TemplateController::class, 'saveItems'])->name('save-items')->middleware('permission:problem_templates.manage');

            // Library — Categories + Items taxonomy (managed via the Problem
            // Library card grid + drill-down; endpoints below).
            Route::post('/categories',             [ProblemTemplates\LibraryController::class, 'storeCategory'])->name('categories.store')->middleware('permission:problem_templates.manage');
            Route::put('/categories/{category}',   [ProblemTemplates\LibraryController::class, 'updateCategory'])->name('categories.update')->middleware('permission:problem_templates.manage');
            Route::delete('/categories/{category}',[ProblemTemplates\LibraryController::class, 'destroyCategory'])->name('categories.destroy')->middleware('permission:problem_templates.manage');
            Route::post('/categories/reorder',     [ProblemTemplates\LibraryController::class, 'reorderCategories'])->name('categories.reorder')->middleware('permission:problem_templates.manage');
            Route::post('/items',                  [ProblemTemplates\LibraryController::class, 'storeItem'])->name('items.store')->middleware('permission:problem_templates.manage');
            Route::put('/items/{item}',            [ProblemTemplates\LibraryController::class, 'updateItem'])->name('items.update')->middleware('permission:problem_templates.manage');
            Route::delete('/items/{item}',         [ProblemTemplates\LibraryController::class, 'destroyItem'])->name('items.destroy')->middleware('permission:problem_templates.manage');
            Route::post('/items/reorder',          [ProblemTemplates\LibraryController::class, 'reorderItems'])->name('items.reorder')->middleware('permission:problem_templates.manage');

            // Equipment attachment
            Route::get('/equipment',               [ProblemTemplates\EquipmentTemplateController::class, 'index'])->name('equipment');
            Route::post('/equipment/attach',       [ProblemTemplates\EquipmentTemplateController::class, 'attach'])->name('equipment.attach')->middleware('permission:problem_templates.manage');
            Route::post('/equipment/bulk-apply',   [ProblemTemplates\EquipmentTemplateController::class, 'bulkApply'])->name('equipment.bulk-apply')->middleware('permission:problem_templates.manage');
        });

        Route::prefix('tickets')->name('tickets.')->middleware('permission:service_tickets.view')->group(function () {
            // No ticket index — the Operations Board IS the ticket list. Only
            // intake + the individual workbench live here.
            Route::get('/create',           Tickets\CreateController::class)->name('create');
            // Intake lookups — static paths, declared before /{ticket} so
            // route-model binding never claims them.
            Route::get('/order-search',     Tickets\OrderSearchController::class)->name('order-search');
            Route::get('/equipment-search', Tickets\EquipmentSearchController::class)->name('equipment-search');
            Route::post('/',                Tickets\StoreController::class)->name('store')->middleware('permission:service_tickets.manage');
            Route::get('/{ticket}',         Tickets\ShowController::class)->name('show');
            Route::get('/{ticket}/edit',    Tickets\EditController::class)->name('edit');
            Route::put('/{ticket}',         Tickets\UpdateController::class)->name('update')->middleware('permission:service_tickets.manage');
            Route::post('/{ticket}/status', Tickets\StatusController::class)->name('status')->middleware('permission:service_tickets.manage');

            // Phase 2B — billing preparation (no payment processing)
            Route::post('/{ticket}/labor',                  LaborEntries\StoreController::class)->name('labor.store')->middleware('permission:service_tickets.manage');
            Route::delete('/{ticket}/labor/{laborEntry}',   LaborEntries\DestroyController::class)->name('labor.destroy')->middleware('permission:service_tickets.manage');
            Route::post('/{ticket}/charges',                ChargeLines\StoreController::class)->name('charges.store')->middleware('permission:service_tickets.manage');
            Route::delete('/{ticket}/charges/{chargeLine}', ChargeLines\DestroyController::class)->name('charges.destroy')->middleware('permission:service_tickets.manage');

            // Phase 4 — workbench: structured diagnostic steps + ticket notes
            Route::post('/{ticket}/diagnostic-steps',          DiagnosticSteps\StoreController::class)->name('diagnostic-steps.store')->middleware('permission:service_tickets.manage');
            Route::delete('/{ticket}/diagnostic-steps/{step}', DiagnosticSteps\DestroyController::class)->name('diagnostic-steps.destroy')->middleware('permission:service_tickets.manage');
            Route::post('/{ticket}/notes',                     Notes\StoreController::class)->name('notes.store')->middleware('permission:service_tickets.manage');
            Route::put('/{ticket}/notes/{note}',               Notes\UpdateController::class)->name('notes.update')->middleware('permission:service_tickets.manage');

            // Phase 2C — repair record (parts, media, timeline)
            Route::post('/{ticket}/parts',           Parts\StoreController::class)->name('parts.store')->middleware('permission:service_tickets.manage');
            Route::delete('/{ticket}/parts/{part}',  Parts\DestroyController::class)->name('parts.destroy')->middleware('permission:service_tickets.manage');
            Route::post('/{ticket}/media',           Media\StoreController::class)->name('media.store')->middleware('permission:service_tickets.manage');
            Route::delete('/{ticket}/media/{media}', Media\DestroyController::class)->name('media.destroy')->middleware('permission:service_tickets.manage');

            // Phase 2D — diagnostic-first lifecycle
            Route::post('/{ticket}/diagnostic/start',    Diagnostics\StartController::class)->name('diagnostic.start')->middleware('permission:service_tickets.manage');
            Route::post('/{ticket}/diagnostic/complete', Diagnostics\CompleteController::class)->name('diagnostic.complete')->middleware('permission:service_tickets.manage');
            Route::put('/{ticket}/diagnostic',           Diagnostics\UpdateController::class)->name('diagnostic.update')->middleware('permission:service_tickets.manage');
            Route::post('/{ticket}/responsibility',      Diagnostics\DecideController::class)->name('responsibility.decide')->middleware('permission:service_tickets.manage');

            // Phase 2E — approval & repair funding gate (state tracking only)
            Route::post('/{ticket}/approval/estimate-sent', Approvals\SendEstimateController::class)->name('approval.estimate-sent')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/approval/approve',       Approvals\ApproveController::class)->name('approval.approve')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/approval/decline',       Approvals\DeclineController::class)->name('approval.decline')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/approval/revoke',        Approvals\RevokeController::class)->name('approval.revoke')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/repair-authorization',        RepairAuthorization\StoreController::class)->name('authorization.store')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/repair-authorization/revoke',   RepairAuthorization\RevokeController::class)->name('authorization.revoke')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/repair-authorization/override', RepairAuthorization\OverrideController::class)->name('authorization.override')->middleware('permission:service_tickets.authorize');
            Route::put('/{ticket}/deposit',           Deposit\UpdateController::class)->name('deposit.update')->middleware('permission:service_tickets.authorize');
            Route::post('/{ticket}/deposit/override', Deposit\OverrideController::class)->name('deposit.override')->middleware('permission:service_tickets.authorize');

            // Phase 3A — customer settlement preview & handoff to the Financial Engine
            Route::get('/{ticket}/settlement',  Settlement\PreviewController::class)->name('settlement.preview');
            Route::post('/{ticket}/settlement', Settlement\StoreController::class)->name('settlement.store')->middleware('permission:service_tickets.bill');
            Route::put('/{ticket}/labor/{laborEntry}',   LaborEntries\UpdateController::class)->name('labor.update')->middleware('permission:service_tickets.manage');
            Route::put('/{ticket}/charges/{chargeLine}', ChargeLines\UpdateController::class)->name('charges.update')->middleware('permission:service_tickets.manage');
            Route::put('/{ticket}/parts/{part}',         Parts\UpdateController::class)->name('parts.update')->middleware('permission:service_tickets.manage');
        });
    });
