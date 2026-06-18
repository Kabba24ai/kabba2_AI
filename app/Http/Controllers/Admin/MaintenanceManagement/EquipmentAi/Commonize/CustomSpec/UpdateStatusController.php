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
            'is_key_comparison' => 'required|boolean',
            'is_ignored'        => 'required|boolean',
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
                ['display_label' => $spec->spec_label]
            );
        } else {
            EquipmentCategoryComparisonKey::where('category_id', $spec->category_id)
                ->where('spec_key', $spec->spec_key)
                ->delete();
        }

        return response()->json(['success' => true]);
    }
}
