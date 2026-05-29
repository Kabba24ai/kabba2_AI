<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // All leaf categories (or all categories) for the selector
        $categories = ProductCategory::orderBy('title')->get(['id', 'title']);

        $profiles = collect();
        $selectedCategory = null;

        if ($request->filled('category_id')) {
            $selectedCategory = ProductCategory::find($request->category_id);

            $profiles = EquipmentAiProfile::with('specifications')
                ->where('category_id', $request->category_id)
                ->orderBy('make')
                ->orderBy('model')
                ->get();
        }

        return view('admin.maintenance_management.equipment_ai.index', compact(
            'categories',
            'profiles',
            'selectedCategory',
        ));
    }
}
