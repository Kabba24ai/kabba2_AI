<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Dispatch\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\ShowController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\DriverSummaryController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\PriorityController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\ReorderController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\LoadController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\AiRulesController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\SaveSettingsController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\SaveDriverCapabilityController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\SaveTruckController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\SaveTrailerController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\SaveEquipmentRuleController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\RunDraftController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\SavePolicyController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\DeleteTruckController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\DeleteTrailerController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence\IndexController as IntelRuleIndexController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence\StoreController as IntelRuleStoreController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence\UpdateController as IntelRuleUpdateController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence\DestroyController as IntelRuleDestroyController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence\ApproveController as IntelRuleApproveController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\AiRules\Intelligence\AiGenerateController as IntelRuleAiGenerateController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\DispatchDateController;

Route::prefix('dispatch')
->name('dispatch.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
    Route::get('/driver-cards', [IndexController::class, 'driverCards'])->name('driver-cards');
    Route::get('/drivers', DriverSummaryController::class)->name('drivers');

    // Drag-and-drop board: atomic reorder / cross-driver reassign. Literal path,
    // declared before the /{unique_id} catch-all below.
    Route::post('/reorder', ReorderController::class)->name('reorder');

    // Combined dispatch loads (group several order_product legs onto one driver).
    // Literal paths, declared before the /{unique_id} catch-all below.
    Route::post('/loads', [LoadController::class, 'store'])->name('loads.store');
    Route::post('/loads/{load}/assign', [LoadController::class, 'assign'])->name('loads.assign');
    Route::post('/loads/{load}/remove-member', [LoadController::class, 'removeMember'])->name('loads.remove-member');
    Route::delete('/loads/{load}', [LoadController::class, 'destroy'])->name('loads.destroy');

    // AI Rules — must be before /{unique_id} catch-all
    Route::get('/ai-rules', AiRulesController::class)->name('ai-rules.index');
    Route::post('/ai-rules/settings', SaveSettingsController::class)->name('ai-rules.settings.save');
    Route::post('/ai-rules/driver-capability', SaveDriverCapabilityController::class)->name('ai-rules.driver-capability.save');
    Route::post('/ai-rules/truck', SaveTruckController::class)->name('ai-rules.truck.save');
    Route::delete('/ai-rules/truck/{id}', DeleteTruckController::class)->name('ai-rules.truck.delete');
    Route::post('/ai-rules/trailer', SaveTrailerController::class)->name('ai-rules.trailer.save');
    Route::delete('/ai-rules/trailer/{id}', DeleteTrailerController::class)->name('ai-rules.trailer.delete');
    Route::post('/ai-rules/equipment-rule', SaveEquipmentRuleController::class)->name('ai-rules.equipment-rule.save');
    Route::post('/ai-rules/policy', SavePolicyController::class)->name('ai-rules.policy.save');
    Route::post('/ai-rules/run-draft', RunDraftController::class)->name('ai-rules.run-draft');

    // Intelligence Rules
    Route::prefix('/ai-rules/intelligence-rules')->name('ai-rules.intelligence-rules.')->group(function () {
        Route::get('/',                        IntelRuleIndexController::class)->name('index');
        Route::post('/',                       IntelRuleStoreController::class)->name('store');
        Route::put('/{rule}',                  IntelRuleUpdateController::class)->name('update');
        Route::delete('/{rule}',               IntelRuleDestroyController::class)->name('destroy');
        Route::post('/{rule}/approve',         IntelRuleApproveController::class)->name('approve');
        Route::post('/ai-generate',            IntelRuleAiGenerateController::class)->name('ai-generate');
    });

    // Dispatch detail / driver checklist for one order product
    Route::get('/{unique_id}', [ShowController::class, 'show'])->name('show');
    Route::post('/{unique_id}/checklist', [ShowController::class, 'saveChecklist'])->name('checklist.save');
    Route::post('/{unique_id}/priority', PriorityController::class)->name('priority');
    Route::post('/{unique_id}/dispatch-date', DispatchDateController::class)->name('dispatch-date');
});
