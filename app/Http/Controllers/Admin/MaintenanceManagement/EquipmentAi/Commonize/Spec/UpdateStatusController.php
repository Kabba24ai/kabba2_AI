<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\Spec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateStatusController extends Controller
{
    /**
     * Instantly persist Key / Ignored / Normal status for an extracted spec key
     * across all profiles in the category.
     *
     * PATCH /commonize/specs/status
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id'              => 'required|exists:product_categories,id',
            'spec_key'                 => 'required|string|max:100',
            'is_key_comparison'        => 'required|boolean',
            'is_ignored'               => 'required|boolean',
            'upgrade_exceeds_value'    => 'nullable|boolean',
            'caution_if_exceeds_value' => 'nullable|boolean',
            'upgrade_is_below_value'   => 'nullable|boolean',
            'caution_if_below_value'   => 'nullable|boolean',
        ]);

        $categoryId = (int) $data['category_id'];
        $specKey    = $data['spec_key'];
        $isKey      = (bool) $data['is_key_comparison'];
        $isIgnored  = !$isKey && (bool) $data['is_ignored'];

        $profileIds = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');

        // Update all extracted spec rows for this key across every profile in the category
        EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
            ->where('spec_key', $specKey)
            ->update([
                'is_key_comparison' => $isKey,
                'is_ignored'        => $isIgnored,
            ]);

        // Sync category-level comparison key
        if ($isKey) {
            $label = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
                ->where('spec_key', $specKey)
                ->value('spec_label') ?? $specKey;

            EquipmentCategoryComparisonKey::updateOrCreate(
                ['category_id' => $categoryId, 'spec_key' => $specKey],
                [
                    'display_label'           => $label,
                    'upgrade_exceeds_value'    => (bool) ($data['upgrade_exceeds_value']    ?? true),
                    'caution_if_exceeds_value' => (bool) ($data['caution_if_exceeds_value'] ?? false),
                    'upgrade_is_below_value'   => (bool) ($data['upgrade_is_below_value']   ?? false),
                    'caution_if_below_value'   => (bool) ($data['caution_if_below_value']   ?? false),
                ]
            );
        } else {
            EquipmentCategoryComparisonKey::where('category_id', $categoryId)
                ->where('spec_key', $specKey)
                ->delete();
        }

        return response()->json(['success' => true]);
    }
}
