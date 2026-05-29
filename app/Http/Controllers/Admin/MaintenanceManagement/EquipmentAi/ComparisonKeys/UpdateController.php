<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;

class UpdateController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $key = EquipmentCategoryComparisonKey::findOrFail($id);

        $data = $request->validate([
            'display_label'    => 'required|string|max:150',
            'importance_level' => 'required|in:critical,high,medium,low',
            'comparison_type'  => 'required|in:higher_is_better,lower_is_better,must_match,range_acceptable,informational_only',
            'sort_order'       => 'nullable|integer|min:0',
            'is_required'      => 'nullable|boolean',
        ]);

        $data['sort_order']  = $data['sort_order'] ?? 0;
        $data['is_required'] = $request->boolean('is_required');

        $key->update($data);

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $key->category_id, 'tab' => 'comparison'])
            ->with('success', 'Comparison key updated.');
    }
}
