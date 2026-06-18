<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatchFlagsController extends Controller
{
    /**
     * Update only the 4 boolean flags on a comparison key.
     * Called by the "View Key Comparison Flags" inline editor on the matrix page.
     *
     * PATCH /comparison-keys/{id}/flags
     * Body: { upgrade_exceeds_value, caution_if_exceeds_value, upgrade_is_below_value, caution_if_below_value }
     */
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $key = EquipmentCategoryComparisonKey::findOrFail($id);

        $data = $request->validate([
            'upgrade_exceeds_value'    => 'required|boolean',
            'caution_if_exceeds_value' => 'required|boolean',
            'upgrade_is_below_value'   => 'required|boolean',
            'caution_if_below_value'   => 'required|boolean',
        ]);

        $key->update([
            'upgrade_exceeds_value'    => (bool) $data['upgrade_exceeds_value'],
            'caution_if_exceeds_value' => (bool) $data['caution_if_exceeds_value'],
            'upgrade_is_below_value'   => (bool) $data['upgrade_is_below_value'],
            'caution_if_below_value'   => (bool) $data['caution_if_below_value'],
        ]);

        return response()->json(['success' => true]);
    }
}
