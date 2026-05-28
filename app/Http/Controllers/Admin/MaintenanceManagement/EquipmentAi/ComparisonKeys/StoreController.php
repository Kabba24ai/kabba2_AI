<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;

class StoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'category_id'      => 'required|exists:product_categories,id',
            'spec_key'         => 'required|string|max:100',
            'display_label'    => 'required|string|max:150',
            'importance_level' => 'required|in:critical,high,medium,low',
            'comparison_type'  => 'required|in:higher_is_better,lower_is_better,must_match,range_acceptable,informational_only',
            'sort_order'       => 'nullable|integer|min:0',
            'is_required'      => 'nullable|boolean',
        ]);

        // Sanitise spec_key
        $data['spec_key']    = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($data['spec_key'])));
        $data['sort_order']  = $data['sort_order'] ?? 0;
        $data['is_required'] = $request->boolean('is_required');

        EquipmentCategoryComparisonKey::updateOrCreate(
            ['category_id' => $data['category_id'], 'spec_key' => $data['spec_key']],
            $data
        );

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.comparison-keys.index', ['category_id' => $data['category_id']])
            ->with('success', 'Comparison key saved.');
    }
}
