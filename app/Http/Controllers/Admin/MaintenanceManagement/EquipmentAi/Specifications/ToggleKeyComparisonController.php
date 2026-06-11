<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToggleKeyComparisonController extends Controller
{
    /**
     * Toggle the is_key_comparison flag on a single AI specification.
     *
     * When enabled  → upserts an entry in equipment_category_comparison_keys
     *                 so the spec is registered at the category level.
     * When disabled → deletes the matching category-level record (if it exists).
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'is_key_comparison' => ['nullable', 'boolean'],
            'upgrade_exceeds_value' => ['nullable', 'boolean'],
            'caution_if_exceeds_value' => ['nullable', 'boolean'],
            'upgrade_is_below_value' => ['nullable', 'boolean'],
            'caution_if_below_value' => ['nullable', 'boolean'],
        ]);

        $spec = EquipmentAiSpecification::with('profile')->findOrFail($id);

        $newValue   = array_key_exists('is_key_comparison', $payload)
            ? (bool) $payload['is_key_comparison']
            : ! $spec->is_key_comparison;
        $categoryId = $spec->profile->category_id;
        $normalizedSpecKey = mb_strtolower(trim((string) $spec->spec_key));

        if ($newValue) {
            $conflictingSpec = EquipmentAiSpecification::query()
                ->join('equipment_ai_profiles', 'equipment_ai_profiles.id', '=', 'equipment_ai_specifications.equipment_ai_profile_id')
                ->where('equipment_ai_profiles.category_id', $categoryId)
                ->where('equipment_ai_specifications.is_key_comparison', true)
                ->where('equipment_ai_specifications.id', '!=', $spec->id)
                ->whereRaw('LOWER(TRIM(equipment_ai_specifications.spec_key)) = ?', [$normalizedSpecKey])
                ->first([
                    'equipment_ai_profiles.make',
                    'equipment_ai_profiles.model',
                ]);

            if ($conflictingSpec) {
                $profileLabel = trim(((string) $conflictingSpec->make) . ' ' . ((string) $conflictingSpec->model));

                return response()->json([
                    'success' => false,
                    'message' => 'This specification is already set as key comparison in category by ' . ($profileLabel !== '' ? $profileLabel : 'another profile') . '.',
                ], 422);
            }
        }

        $spec->update(['is_key_comparison' => $newValue]);

        if ($newValue) {
            // ── Checking: add / update the category-level key ─────────────────
            EquipmentCategoryComparisonKey::updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'spec_key'    => $spec->spec_key,
                ],
                [
                    'display_label'    => $spec->spec_label,
                    'importance_level' => 'medium',
                    'comparison_type'  => 'informational_only',
                    'sort_order'       => 0,
                    'is_required'      => false,
                    'upgrade_exceeds_value' => (bool) ($payload['upgrade_exceeds_value'] ?? true),
                    'caution_if_exceeds_value' => (bool) ($payload['caution_if_exceeds_value'] ?? true),
                    'upgrade_is_below_value' => (bool) ($payload['upgrade_is_below_value'] ?? false),
                    'caution_if_below_value' => (bool) ($payload['caution_if_below_value'] ?? false),
                ]
            );
        } else {
            // Remove category key only when no other profile still uses it as key.
            $isUsedByAnotherProfile = EquipmentAiSpecification::query()
                ->join('equipment_ai_profiles', 'equipment_ai_profiles.id', '=', 'equipment_ai_specifications.equipment_ai_profile_id')
                ->where('equipment_ai_profiles.category_id', $categoryId)
                ->where('equipment_ai_specifications.is_key_comparison', true)
                ->where('equipment_ai_specifications.id', '!=', $spec->id)
                ->whereRaw('LOWER(TRIM(equipment_ai_specifications.spec_key)) = ?', [$normalizedSpecKey])
                ->exists();

            if (! $isUsedByAnotherProfile) {
                EquipmentCategoryComparisonKey::where('category_id', $categoryId)
                    ->where('spec_key', $spec->spec_key)
                    ->delete();
            }
        }

        return response()->json([
            'success'           => true,
            'is_key_comparison' => $newValue,
            'flags' => [
                'upgrade_exceeds_value' => (bool) ($payload['upgrade_exceeds_value'] ?? true),
                'caution_if_exceeds_value' => (bool) ($payload['caution_if_exceeds_value'] ?? true),
                'upgrade_is_below_value' => (bool) ($payload['upgrade_is_below_value'] ?? false),
                'caution_if_below_value' => (bool) ($payload['caution_if_below_value'] ?? false),
            ],
        ]);
    }
}
