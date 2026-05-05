<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __invoke(Request $request, string $uniqueId): JsonResponse
    {
        $equipment = Equipment::where('unique_id', $uniqueId)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
            'default_weight' => ['nullable', 'integer', 'between:0,100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $baseKey = (string) Str::of($validated['name'])->snake();
        $criteriaKey = $baseKey !== '' ? $baseKey : 'criteria';

        $suffix = 1;
        while (EquipmentCriticalMatchingCriterion::where('product_category_id', $equipment->product_category_id)
            ->where('criteria_key', $criteriaKey)
            ->exists()) {
            $criteriaKey = $baseKey . '_' . $suffix;
            $suffix++;
        }

        $criterion = EquipmentCriticalMatchingCriterion::create([
            'product_category_id' => $equipment->product_category_id,
            'criteria_key' => $criteriaKey,
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'default_weight' => $validated['default_weight'] ?? 50,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
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
