<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\KeyComparison;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentKeyComparison;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(string $unique_id): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        $comparisons = EquipmentKeyComparison::where('equipment_id', $equipment->id)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($comparison) {
                return [
                    'id' => $comparison->id,
                    'spec_label' => $comparison->spec_label,
                    'spec_value' => $comparison->spec_value,
                    'spec_unit' => $comparison->spec_unit,
                    'is_manual_override' => $comparison->is_manual_override,
                    'sort_order' => $comparison->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'items' => $comparisons,
        ]);
    }
}
