<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\KeyComparison;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentKeyComparison;
use Illuminate\Http\JsonResponse;

class DeleteController extends Controller
{
    public function __invoke(string $unique_id, int $comparison_id): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        $comparison = EquipmentKeyComparison::where('id', $comparison_id)
            ->where('equipment_id', $equipment->id)
            ->firstOrFail();

        $comparison->delete();

        EquipmentKeyComparison::where('equipment_id', $equipment->id)
            ->orderBy('sort_order')
            ->get()
            ->each(fn ($item, $idx) => $item->update(['sort_order' => $idx]));

        return response()->json(['success' => true]);
    }
}
