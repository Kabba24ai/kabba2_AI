<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria\IndexRequest;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $categoryId = (int) $request->validated()['category_id'];

        $criteria = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('is_active', true)
            ->where('is_key_criteria', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'category_id' => $categoryId,
            'items' => $criteria->map(function (EquipmentCriticalMatchingCriterion $item) {
                return [
                    'id' => (int) $item->id,
                    'key' => (string) $item->criteria_key,
                    'name' => (string) $item->name,
                    'unit' => (string) ($item->unit ?? ''),
                    'default_weight' => (int) $item->default_weight,
                    'upgrade_exceeds_value' => (bool) $item->upgrade_exceeds_value,
                    'caution_if_change_value' => (bool) $item->caution_if_change_value,
                    'upgrade_is_below_value' => (bool) $item->upgrade_is_below_value,
                    'caution_if_below_value' => (bool) $item->caution_if_below_value,
                    'sort_order' => (int) $item->sort_order,
                    'is_active' => (bool) $item->is_active,
                    'is_key_criteria' => (bool) $item->is_key_criteria,
                    'source_type' => (string) ($item->source_type ?? 'manual'),
                ];
            })->values(),
            'ai_items' => $this->getAiSpecs($categoryId),
        ]);
    }

    private function getAiSpecs(int $categoryId): array
    {
        return EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('is_active', true)
            ->where('source_type', 'ai')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => (int) $item->id,
                    'spec_key' => (string) ($item->criteria_key ?? ''),
                    'spec_label' => (string) ($item->name ?? ''),
                    'unit' => (string) ($item->unit ?? ''),
                    'is_key_criteria' => (bool) $item->is_key_criteria,
                    'source_type' => (string) ($item->source_type ?? 'ai'),
                ];
            })
            ->values()
            ->all();
    }
}
