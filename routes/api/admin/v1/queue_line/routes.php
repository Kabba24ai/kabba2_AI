<?php

use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineBoardController;
use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineEquipmentCandidatesController;
use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineHistoryController;
use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineItemController;
use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineSummaryController;
use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineSwitchEquipmentController;
use App\Http\Controllers\Api\Admin\V1\QueueLine\QueueLineVerifyFuelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Queue Line — standalone mobile module API (api/admin/v1/queue-line)
|--------------------------------------------------------------------------
| One coherent route family for every mobile Queue Line operation. Items
| are addressed by order_product unique_id only. Controllers are thin
| adapters over the canonical services in app/Services/QueueLine — no
| eligibility, urgency, fuel, or assignment rule lives at this layer.
| Fuel REVERSAL is deliberately absent: supervised web-only (approved).
| Contract doc: docs/queue-line-audit-2026-07-19/MOBILE_API_CONTRACT.md
*/

Route::group(['prefix' => 'queue-line'], function () {
    Route::get('/', QueueLineBoardController::class);
    Route::get('/summary', QueueLineSummaryController::class);
    Route::get('/{order_product_unique_id}', QueueLineItemController::class);
    Route::get('/{order_product_unique_id}/history', QueueLineHistoryController::class);
    Route::get('/{order_product_unique_id}/equipment-candidates', QueueLineEquipmentCandidatesController::class);
    Route::post('/{order_product_unique_id}/switch-equipment', QueueLineSwitchEquipmentController::class);
    Route::post('/{order_product_unique_id}/verify-fuel', QueueLineVerifyFuelController::class);
});
