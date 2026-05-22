<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria\AiStoreRequest;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AiStoreController extends Controller
{
    public function __invoke(AiStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $categoryId = (int) $validated['category_id'];

        $baseKey = (string) Str::of($validated['name'])->snake();
        $baseKey = trim($baseKey, '_');
        $baseKey = $baseKey !== '' ? $baseKey : 'specification';

        $criterion = $this->resolveAiCriterion($categoryId, $baseKey, $validated['name']);

        $criterion->forceFill([
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'source_type' => 'ai',
            'default_weight' => $criterion->exists ? (int) $criterion->default_weight : 50,
            'upgrade_exceeds_value' => false,
            'caution_if_change_value' => false,
            'upgrade_is_below_value' => false,
            'caution_if_below_value' => false,
            'sort_order' => $criterion->exists ? (int) $criterion->sort_order : $this->nextSortOrder($categoryId),
            'is_active' => true,
            'is_key_criteria' => false,
        ])->save();

        return response()->json([
            'success' => true,
            'item' => [
                'id' => (int) $criterion->id,
                'spec_key' => (string) $criterion->criteria_key,
                'spec_label' => (string) $criterion->name,
                'unit' => (string) ($criterion->unit ?? ''),
                'is_key_criteria' => false,
                'source_type' => 'ai',
            ],
        ]);
    }

    private function resolveAiCriterion(int $categoryId, string $baseKey, string $label): EquipmentCriticalMatchingCriterion
    {
        $existingByLabel = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('source_type', 'ai')
            ->where('is_key_criteria', false)
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($label))])
            ->first();

        if ($existingByLabel) {
            return $existingByLabel;
        }

        $suffix = 0;
        while (true) {
            $candidate = $suffix === 0 ? $baseKey : $baseKey . '_' . $suffix;

            $conflict = EquipmentCriticalMatchingCriterion::query()
                ->where('product_category_id', $categoryId)
                ->where('criteria_key', $candidate)
                ->first();

            if (!$conflict) {
                return new EquipmentCriticalMatchingCriterion([
                    'product_category_id' => $categoryId,
                    'criteria_key' => $candidate,
                ]);
            }

            if (($conflict->source_type ?? '') === 'ai' && $conflict->is_key_criteria === false) {
                return $conflict;
            }

            $suffix++;
        }
    }

    private function nextSortOrder(int $categoryId): int
    {
        $maxSortOrder = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->max('sort_order');

        return max(0, (int) $maxSortOrder + 1);
    }
}
