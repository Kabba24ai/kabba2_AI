<?php

use Illuminate\Support\Facades\Route;

// Controllers — AI Profiles
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ScanController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\RefreshController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\DeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ResetCategoryController;

// Controllers — Profile Specifications
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\IndexController as SpecIndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\StoreController as SpecStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\UpdateController as SpecUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\DeleteController as SpecDeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\GenerateController as SpecGenerateController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\ToggleKeyComparisonController as SpecToggleKeyComparisonController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\DeepResearchController as SpecDeepResearchController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\FillGapsController as SpecFillGapsController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\ResearchForProfileController as SpecResearchForProfileController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\SetValueController as SpecSetValueController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications\PropagateLabelController as SpecPropagateLabelController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\CompareController as EquipmentAiCompareController;

// Controllers — Commonize (Compare & Commonize)
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\AnalyzeController as CommonizeAnalyzeController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\ApplyController as CommonizeApplyController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\MatrixController as CommonizeMatrixController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\SaveFrameworkController as CommonizeSaveFrameworkController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec\StoreController as CustomSpecStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec\DeleteController as CustomSpecDeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec\UpdateStatusController as CustomSpecUpdateStatusController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec\UpdateValueController as CustomSpecUpdateValueController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec\ResearchController as CustomSpecResearchController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\Spec\UpdateStatusController as SpecUpdateStatusController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\Spec\DeleteController as CommonizeSpecDeleteController;

// Controllers — Category Comparison Keys
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys\IndexController as KeyIndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys\StoreController as KeyStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys\UpdateController as KeyUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys\DeleteController as KeyDeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys\PatchFlagsController as KeyPatchFlagsController;

Route::prefix('equipment-ai')
    ->name('equipment-ai.')
    ->group(function () {

        /*
        |-----------------------------------------------------------
        | AI Profile List & Category Scan
        |-----------------------------------------------------------
        | Full name: admin.maintenance-management.equipment-ai.index
        |            admin.maintenance-management.equipment-ai.scan
        |            admin.maintenance-management.equipment-ai.profiles.delete
        */
        Route::get('/', IndexController::class)->name('index');
        Route::post('/scan', ScanController::class)->name('scan');
        Route::post('/refresh', RefreshController::class)->name('refresh');
        Route::post('/reset-category', ResetCategoryController::class)->name('reset-category');
        Route::delete('/profiles/{unique_id}', DeleteController::class)->name('profiles.delete');

        /*
        |-----------------------------------------------------------
        | Compare & Commonize
        |-----------------------------------------------------------
        | Full name: admin.maintenance-management.equipment-ai.commonize.analyze
        |            admin.maintenance-management.equipment-ai.commonize.apply
        */
        Route::prefix('commonize')
            ->name('commonize.')
            ->group(function () {
                Route::get('/matrix',          CommonizeMatrixController::class)->name('matrix');
                Route::post('/analyze',        CommonizeAnalyzeController::class)->name('analyze');
                Route::post('/apply',          CommonizeApplyController::class)->name('apply');
                Route::post('/save-framework', CommonizeSaveFrameworkController::class)->name('save-framework');

                // Custom (user-defined) specifications
                Route::post('/custom-specs',                           CustomSpecStoreController::class)->name('custom-specs.store');
                Route::delete('/custom-specs/{id}',                    CustomSpecDeleteController::class)->name('custom-specs.delete');
                Route::patch('/custom-specs/{id}/status',              CustomSpecUpdateStatusController::class)->name('custom-specs.update-status');
                Route::patch('/custom-spec-values/{id}',               CustomSpecUpdateValueController::class)->name('custom-spec-values.update');
                Route::post('/custom-specs/{id}/research',             CustomSpecResearchController::class)->name('custom-specs.research');

                // Extracted spec status (instant persist — no Save Framework needed)
                Route::patch('/specs/status', SpecUpdateStatusController::class)->name('specs.update-status');

                // Delete extracted specs by key across all profiles in category
                Route::delete('/specs/delete-by-key', CommonizeSpecDeleteController::class)->name('specs.delete-by-key');
            });

        /*
        |-----------------------------------------------------------
        | Profile Specifications CRUD
        |-----------------------------------------------------------
        | Full name: admin.maintenance-management.equipment-ai.profiles.specifications.*
        */
        Route::prefix('profiles/{unique_id}/specifications')
            ->name('profiles.specifications.')
            ->group(function () {
                Route::get('/',          SpecIndexController::class)->name('index');
                Route::post('/',         SpecStoreController::class)->name('store');
                Route::post('/generate', SpecGenerateController::class)->name('generate');
                Route::post('/fill-gaps', SpecFillGapsController::class)->name('fill-gaps');
            });

        // Spec update/delete/toggle keyed by spec id (not profile unique_id)
        Route::put('/specifications/{id}', SpecUpdateController::class)->name('specifications.update');
        Route::delete('/specifications/{id}', SpecDeleteController::class)->name('specifications.delete');
        Route::post('/specifications/{id}/toggle-key-comparison', SpecToggleKeyComparisonController::class)->name('specifications.toggle-key-comparison');
        Route::post('/specifications/{id}/deep-research', SpecDeepResearchController::class)->name('specifications.deep-research');
        Route::post('/specifications/{id}/propagate-label', SpecPropagateLabelController::class)->name('specifications.propagate-label');

        // Category-wide fill-gaps (no profile context)
        Route::post('/category-fill-gaps', SpecFillGapsController::class)->name('category-fill-gaps');

        // Per-cell matrix research (profile + spec_key, no existing spec_id required)
        Route::post('/specifications/research-for-profile', SpecResearchForProfileController::class)->name('specifications.research-for-profile');

        // Manual value entry from matrix page
        Route::post('/specifications/set-value', SpecSetValueController::class)->name('specifications.set-value');

        // AI comparison report for a category
        Route::post('/compare', EquipmentAiCompareController::class)->name('compare');

        /*
        |-----------------------------------------------------------
        | Category Comparison Keys CRUD
        |-----------------------------------------------------------
        | Full name: admin.maintenance-management.equipment-ai.comparison-keys.*
        */
        Route::prefix('comparison-keys')
            ->name('comparison-keys.')
            ->group(function () {
                Route::get('/', KeyIndexController::class)->name('index');
                Route::post('/', KeyStoreController::class)->name('store');
                Route::put('/{id}', KeyUpdateController::class)->name('update');
                Route::delete('/{id}', KeyDeleteController::class)->name('delete');
                Route::patch('/{id}/flags', KeyPatchFlagsController::class)->name('flags');
            });
    });
