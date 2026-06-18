<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpec;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;

class DeleteController extends Controller
{
    public function __invoke(int $id): JsonResponse
    {
        $spec = EquipmentAiCustomSpec::findOrFail($id);

        // Remove from comparison keys if it was marked Key
        if ($spec->is_key_comparison) {
            EquipmentCategoryComparisonKey::where('category_id', $spec->category_id)
                ->where('spec_key', $spec->spec_key)
                ->delete();
        }

        $spec->delete(); // cascades to custom_spec_values

        return response()->json(['success' => true]);
    }
}
