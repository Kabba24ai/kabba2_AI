<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\Spec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    /**
     * Delete one or more spec keys from every profile in a category.
     * Also removes any matching EquipmentCategoryComparisonKey records.
     *
     * DELETE /commonize/specs/delete-by-key
     * Body: { category_id, spec_keys: string[] }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'spec_keys'   => 'required|array|min:1',
            'spec_keys.*' => 'required|string|max:100',
        ]);

        $categoryId = (int) $data['category_id'];
        $specKeys   = $data['spec_keys'];

        $profileIds = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');

        $deleted = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
            ->whereIn('spec_key', $specKeys)
            ->delete();

        EquipmentCategoryComparisonKey::where('category_id', $categoryId)
            ->whereIn('spec_key', $specKeys)
            ->delete();

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }
}
