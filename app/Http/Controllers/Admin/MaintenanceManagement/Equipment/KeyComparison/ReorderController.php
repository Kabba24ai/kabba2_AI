<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\KeyComparison;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentKeyComparison;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    public function __invoke(Request $request, string $unique_id): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        $order = $request->input('order', []);

        foreach ($order as $index => $comparisonId) {
            EquipmentKeyComparison::where('id', $comparisonId)
                ->where('equipment_id', $equipment->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
