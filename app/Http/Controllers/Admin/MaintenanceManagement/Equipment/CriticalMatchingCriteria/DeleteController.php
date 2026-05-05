<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;

class DeleteController extends Controller
{
    public function __invoke(string $uniqueId, int $criteriaId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $criterion = EquipmentCriticalMatchingCriterion::query()
            ->where('id', $criteriaId)
            ->where('product_category_id', $equipment->product_category_id)
            ->firstOrFail();

        $criterion->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
