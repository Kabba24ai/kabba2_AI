<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropagateLabelController extends Controller
{
    /**
     * Propagate the display label of one spec to all other specs in the same
     * category that share the same spec_key.
     *
     * POST /specifications/{id}/propagate-label
     * Body: { spec_label }
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $spec = EquipmentAiSpecification::findOrFail($id);

        $data = $request->validate([
            'spec_label' => 'required|string|max:150',
        ]);

        if (!filled($spec->spec_key)) {
            return response()->json(['success' => false, 'message' => 'This spec has no key and cannot be propagated.'], 422);
        }

        $categoryId = $spec->profile->category_id;
        $profileIds = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');

        $updated = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
            ->where('spec_key', $spec->spec_key)
            ->where('id', '!=', $spec->id)
            ->update(['spec_label' => $data['spec_label']]);

        // Also update the source spec itself
        $spec->update(['spec_label' => $data['spec_label']]);

        return response()->json([
            'success' => true,
            'updated' => $updated + 1,
            'message' => 'Label propagated to ' . ($updated + 1) . ' specification(s).',
        ]);
    }
}
