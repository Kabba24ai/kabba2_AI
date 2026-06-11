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
            'upgrade_exceeds_value' => 'nullable|boolean',
            'caution_if_exceeds_value' => 'nullable|boolean',
            'upgrade_is_below_value' => 'nullable|boolean',
            'caution_if_below_value' => 'nullable|boolean',
        ]);

        $data['sort_order']  = $data['sort_order'] ?? 0;
        $data['is_required'] = $request->boolean('is_required');
        $data['upgrade_exceeds_value'] = $request->boolean('upgrade_exceeds_value', false);
        $data['caution_if_exceeds_value'] = $request->boolean('caution_if_exceeds_value', false);
        $data['upgrade_is_below_value'] = $request->boolean('upgrade_is_below_value', false);
        $data['caution_if_below_value'] = $request->boolean('caution_if_below_value', false);

        $key->update($data);

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $key->category_id, 'tab' => 'comparison'])
            ->with('success', 'Comparison key updated.');
    }
}
