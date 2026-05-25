<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria\DeleteRequest;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use Illuminate\Http\JsonResponse;

class DeleteController extends Controller
{
    public function __invoke(DeleteRequest $request, int $criteriaId): JsonResponse
    {
        $validated = $request->validated();

        $categoryId = (int) $validated['category_id'];

        $criterion = EquipmentCriticalMatchingCriterion::query()
            ->where('id', $criteriaId)
            ->where('product_category_id', $categoryId)
            ->firstOrFail();

        $criterion->forceFill([
            'is_key_criteria' => false,
            'is_active' => true,
        ])->save();

        return response()->json([
            'success' => true,
        ]);
    }
}
