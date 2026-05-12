<?php

namespace App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\View\View;

class CreateController extends Controller
{
    public function __invoke(): View
    {
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

        return view('admin.product_management.equipment_assignments.create', [
            'categories' => $categories,
            'products' => $products,
            'equipment' => $equipment,
        ]);
    }
}
