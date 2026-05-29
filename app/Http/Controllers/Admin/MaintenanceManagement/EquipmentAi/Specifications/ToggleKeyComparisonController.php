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
     * When enabled  → also upserts an entry in equipment_category_comparison_keys
     *                 so the spec is registered at the category level.
     * When disabled → leaves the category-level record in place
     *                 (admin manages that table independently).
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $spec = EquipmentAiSpecification::with('profile')->findOrFail($id);

        $newValue = ! $spec->is_key_comparison;

        $spec->update(['is_key_comparison' => $newValue]);

        // ── Register in category-level comparison keys when enabling ──────────
        if ($newValue) {
            $categoryId = $spec->profile->category_id;

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
                ]
            );
        }

        return response()->json([
            'success'           => true,
            'is_key_comparison' => $newValue,
        ]);
    }
}
