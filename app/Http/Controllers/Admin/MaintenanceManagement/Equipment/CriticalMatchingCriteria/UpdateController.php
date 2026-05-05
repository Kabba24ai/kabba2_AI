<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, string $uniqueId, int $criteriaId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $criterion = EquipmentCriticalMatchingCriterion::query()
            ->where('id', $criteriaId)
            ->where('product_category_id', $equipment->product_category_id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
            'default_weight' => ['required', 'integer', 'between:0,100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $criterion->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'default_weight' => $validated['default_weight'],
            'sort_order' => $validated['sort_order'] ?? $criterion->sort_order,
            'is_active' => $validated['is_active'] ?? $criterion->is_active,
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $criterion->id,
                'key' => $criterion->criteria_key,
                'name' => $criterion->name,
                'unit' => $criterion->unit,
                'default_weight' => (int) $criterion->default_weight,
                'sort_order' => (int) $criterion->sort_order,
                'is_active' => (bool) $criterion->is_active,
            ],
        ]);
    }
}
