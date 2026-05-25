<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\KeyComparison;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentKeyComparison;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request, string $unique_id): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        $data = $request->validate([
            'spec_label' => 'required|string',
            'spec_value' => 'required|string',
            'spec_unit' => 'nullable|string',
            'is_manual_override' => 'required|boolean',
        ]);

        $maxSort = EquipmentKeyComparison::where('equipment_id', $equipment->id)
            ->max('sort_order') ?? -1;

        $comparison = EquipmentKeyComparison::create(array_merge($data, [
            'equipment_id' => $equipment->id,
            'sort_order' => $maxSort + 1,
        ]));

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $comparison->id,
                'spec_label' => $comparison->spec_label,
                'spec_value' => $comparison->spec_value,
                'spec_unit' => $comparison->spec_unit,
                'is_manual_override' => $comparison->is_manual_override,
                'sort_order' => $comparison->sort_order,
            ],
        ]);
    }
}
