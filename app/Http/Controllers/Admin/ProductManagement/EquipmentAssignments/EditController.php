<?php

namespace App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\ProductEquipmentAssignment;
use Illuminate\View\View;

class EditController extends Controller
{
    public function __invoke(ProductEquipmentAssignment $equipmentAssignment): View
    {
        $equipmentAssignment->load(['paths.items']);

        $products = Product::with(['categories:id,title'])
            ->orderBy('product_name')
            ->get();

        $categories = ProductCategory::query()
            ->select('id', 'title')
            ->orderByAdmin()
            ->get();

        $equipment = Equipment::with('productCategory')
            ->orderBy('equipment_name')
            ->get();

        return view('admin.product_management.equipment_assignments.edit', [
            'assignment' => $equipmentAssignment,
            'categories' => $categories,
            'products' => $products,
            'equipment' => $equipment,
        ]);
    }
}
