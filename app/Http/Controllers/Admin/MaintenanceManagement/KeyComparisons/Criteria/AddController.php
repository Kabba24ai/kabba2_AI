<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria\AddRequest;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;

class AddController extends Controller
{
    public function __invoke(AddRequest $request, int $criteriaId): JsonResponse
    {
        $categoryId = (int) $request->validated()['category_id'];

        $criterion = EquipmentCriticalMatchingCriterion::query()
            ->where('id', $criteriaId)
            ->where('product_category_id', $categoryId)
            ->firstOrFail();

        $criterion->forceFill([
            'is_key_criteria' => true,
            'is_active' => true,
        ])->save();

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
                'is_key_criteria' => (bool) $criterion->is_key_criteria,
                'source_type' => (string) ($criterion->source_type ?? 'manual'),
            ],
        ]);
    }
}
