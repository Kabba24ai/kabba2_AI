<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentKeyComparison;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeyComparisonController extends Controller
{
    /**
     * Get all key comparisons for an equipment
     */
    public function index(Request $request, string $uniqueId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

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

    /**
     * Add a specification to key comparisons
     */
    public function store(Request $request, string $uniqueId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $data = $request->validate([
            'spec_label' => 'required|string',
            'spec_value' => 'required|string',
            'spec_unit' => 'nullable|string',
            'is_manual_override' => 'required|boolean',
        ]);

        // Get max sort order
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

    /**
     * Remove a specification from key comparisons
     */
    public function destroy(Request $request, string $uniqueId, int $comparisonId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $comparison = EquipmentKeyComparison::where('id', $comparisonId)
            ->where('equipment_id', $equipment->id)
            ->firstOrFail();

        $comparison->delete();

        // Reorder remaining items
        EquipmentKeyComparison::where('equipment_id', $equipment->id)
            ->orderBy('sort_order')
            ->get()
            ->each(fn ($item, $idx) => $item->update(['sort_order' => $idx]));

        return response()->json(['success' => true]);
    }

    /**
     * Update sort order (reorder items)
     */
    public function reorder(Request $request, string $uniqueId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $order = $request->input('order', []); // array of comparison IDs in new order

        foreach ($order as $index => $comparisonId) {
            EquipmentKeyComparison::where('id', $comparisonId)
                ->where('equipment_id', $equipment->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
