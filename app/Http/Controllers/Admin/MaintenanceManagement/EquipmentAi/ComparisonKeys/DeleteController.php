<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;

class DeleteController extends Controller
{
    public function __invoke(int $id)
    {
        $key = EquipmentCategoryComparisonKey::findOrFail($id);
        $categoryId = $key->category_id;
        $key->delete();

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.comparison-keys.index', ['category_id' => $categoryId])
            ->with('success', 'Comparison key deleted.');
    }
}
