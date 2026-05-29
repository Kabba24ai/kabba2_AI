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
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId, 'tab' => 'comparison'])
            ->with('success', 'Comparison key deleted.');
    }
}
