<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpec;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateStatusController extends Controller
{
    /**
     * Persist is_key_comparison / is_ignored for a custom spec.
     * Called via PATCH when the user clicks the ★ or ⊘ toggle on the row.
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'is_key_comparison'        => 'required|boolean',
            'is_ignored'               => 'required|boolean',
            'upgrade_exceeds_value'    => 'nullable|boolean',
            'caution_if_exceeds_value' => 'nullable|boolean',
            'upgrade_is_below_value'   => 'nullable|boolean',
            'caution_if_below_value'   => 'nullable|boolean',
        ]);

        $spec = EquipmentAiCustomSpec::findOrFail($id);

        $isKey     = (bool) $data['is_key_comparison'];
        $isIgnored = !$isKey && (bool) $data['is_ignored'];

        $spec->update([
            'is_key_comparison' => $isKey,
            'is_ignored'        => $isIgnored,
        ]);

        if ($isKey) {
            EquipmentCategoryComparisonKey::updateOrCreate(
                ['category_id' => $spec->category_id, 'spec_key' => $spec->spec_key],
                [
                    'display_label'           => $spec->spec_label,
                    'upgrade_exceeds_value'    => (bool) ($data['upgrade_exceeds_value']    ?? true),
                    'caution_if_exceeds_value' => (bool) ($data['caution_if_exceeds_value'] ?? false),
                    'upgrade_is_below_value'   => (bool) ($data['upgrade_is_below_value']   ?? false),
                    'caution_if_below_value'   => (bool) ($data['caution_if_below_value']   ?? false),
                ]
            );
        } else {
            EquipmentCategoryComparisonKey::where('category_id', $spec->category_id)
                ->where('spec_key', $spec->spec_key)
                ->delete();
        }

        return response()->json(['success' => true]);
    }
}
