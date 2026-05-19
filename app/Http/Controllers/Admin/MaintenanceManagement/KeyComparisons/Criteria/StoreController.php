<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria\StoreRequest;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $categoryId = (int) $validated['category_id'];

        $baseKey = (string) Str::of($validated['name'])->snake();
        $criteriaKey = $baseKey !== '' ? $baseKey : 'criteria';

        $suffix = 1;
        while (EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('criteria_key', $criteriaKey)
            ->exists()) {
            $criteriaKey = $baseKey . '_' . $suffix;
            $suffix++;
        }

        $criterion = EquipmentCriticalMatchingCriterion::create([
            'product_category_id' => $categoryId,
            'criteria_key' => $criteriaKey,
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'default_weight' => $validated['default_weight'] ?? 50,
            'upgrade_exceeds_value' => filter_var($validated['upgrade_exceeds_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'caution_if_change_value' => filter_var($validated['caution_if_change_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'upgrade_is_below_value' => filter_var($validated['upgrade_is_below_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'caution_if_below_value' => filter_var($validated['caution_if_below_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => (int) $criterion->id,
                'key' => (string) $criterion->criteria_key,
                'name' => (string) $criterion->name,
                'unit' => (string) ($criterion->unit ?? ''),
                'default_weight' => (int) $criterion->default_weight,
                'upgrade_exceeds_value' => (bool) $criterion->upgrade_exceeds_value,
                'caution_if_change_value' => (bool) $criterion->caution_if_change_value,
                'upgrade_is_below_value' => (bool) $criterion->upgrade_is_below_value,
                'caution_if_below_value' => (bool) $criterion->caution_if_below_value,
                'sort_order' => (int) $criterion->sort_order,
                'is_active' => (bool) $criterion->is_active,
            ],
        ]);
    }
}
