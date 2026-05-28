<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\ComparisonKeys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $categories = ProductCategory::orderBy('title')->get(['id', 'title']);

        $keys = collect();
        $selectedCategory = null;

        if ($request->filled('category_id')) {
            $selectedCategory = ProductCategory::find($request->category_id);

            $keys = EquipmentCategoryComparisonKey::where('category_id', $request->category_id)
                ->orderBy('sort_order')
                ->orderBy('spec_key')
                ->get();
        }

        return view('admin.maintenance_management.equipment_ai.comparison_keys.index', compact(
            'categories',
            'keys',
            'selectedCategory',
        ));
    }
}
