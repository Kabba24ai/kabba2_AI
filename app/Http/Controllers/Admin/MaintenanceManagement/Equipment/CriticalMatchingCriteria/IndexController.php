<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(string $uniqueId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $criteria = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $equipment->product_category_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'items' => $criteria->map(function (EquipmentCriticalMatchingCriterion $item) {
                return [
                    'id' => $item->id,
                    'key' => $item->criteria_key,
                    'name' => $item->name,
                    'unit' => $item->unit,
                    'default_weight' => (int) $item->default_weight,
                    'sort_order' => (int) $item->sort_order,
                    'is_active' => (bool) $item->is_active,
                ];
            })->values(),
        ]);
    }
}
