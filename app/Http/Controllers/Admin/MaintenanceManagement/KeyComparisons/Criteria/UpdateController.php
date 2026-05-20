<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria\UpdateRequest;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, int $criteriaId): JsonResponse
    {
        $validated = $request->validated();

        $categoryId = (int) $validated['category_id'];

        $criterion = EquipmentCriticalMatchingCriterion::query()
            ->where('id', $criteriaId)
            ->where('product_category_id', $categoryId)
            ->firstOrFail();

        $criterion->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'default_weight' => $validated['default_weight'],
            'upgrade_exceeds_value' => filter_var($validated['upgrade_exceeds_value'] ?? $criterion->upgrade_exceeds_value, FILTER_VALIDATE_BOOLEAN),
            'caution_if_change_value' => filter_var($validated['caution_if_change_value'] ?? $criterion->caution_if_change_value, FILTER_VALIDATE_BOOLEAN),
            'upgrade_is_below_value' => filter_var($validated['upgrade_is_below_value'] ?? $criterion->upgrade_is_below_value, FILTER_VALIDATE_BOOLEAN),
            'caution_if_below_value' => filter_var($validated['caution_if_below_value'] ?? $criterion->caution_if_below_value, FILTER_VALIDATE_BOOLEAN),
            'sort_order' => $validated['sort_order'] ?? $criterion->sort_order,
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
