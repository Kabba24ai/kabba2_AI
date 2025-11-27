<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\PartsList;
use App\Models\ProductManagement\ProductCategory;


class EditController extends Controller
{
    public function __invoke($unique_id)
    {

        $list = PartsList::with(['creator', 'category', 'parts'])
            ->where('unique_id', $unique_id)
            ->first();

        $categories = ProductCategory::with('products', 'equipments')->get();

        $parts = Part::with('category')->get();

        $selectedPartIds = $list->parts->pluck('id')->toArray();

        return view('admin.maintenance_management.parts.parts_list.edit', compact('list', 'categories', 'parts', 'selectedPartIds'));
    }
}
