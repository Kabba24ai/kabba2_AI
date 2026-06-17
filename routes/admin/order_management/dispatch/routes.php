<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Dispatch\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\ShowController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\DriverSummaryController;
use App\Http\Controllers\Admin\OrderManagement\Dispatch\PriorityController;
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

Route::prefix('dispatch')
->name('dispatch.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
    Route::get('/driver-cards', [IndexController::class, 'driverCards'])->name('driver-cards');
    Route::get('/drivers', DriverSummaryController::class)->name('drivers');

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

    // Dispatch detail / driver checklist for one order product
    Route::get('/{unique_id}', [ShowController::class, 'show'])->name('show');
    Route::post('/{unique_id}/checklist', [ShowController::class, 'saveChecklist'])->name('checklist.save');
    Route::post('/{unique_id}/priority', PriorityController::class)->name('priority');
});
