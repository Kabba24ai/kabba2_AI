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

        // $categories = ProductCategory::with('products', 'equipments')->get();
        // Load categories with sorted relationships
        // $categories = ProductCategory::with([
        //     'products',
        //     'equipments' => function ($q) {
        //         $q->orderBy('equipment_name', 'asc'); // SORT HERE
        //     }
        // ])->get();

         $hierarchy = ProductCategory::getHierarchy();
        
            $categories = ProductCategory::with([
                'products',
                'equipments' => fn ($q) => $q->orderBy('equipment_name', 'asc'),
            ])
            ->whereIn('id', array_keys($hierarchy))
            ->get()
            ->sortBy(fn ($cat) => array_search($cat->id, array_keys($hierarchy)))
            ->values();

            $categories->each(function ($category) use ($hierarchy) {
                $category->hierarchy_title = $hierarchy[$category->id] ?? $category->title;
            });

        $parts = Part::with('category')->orderBy('part_name')->get();


        $selectedPartIds = $list->parts->pluck('id')->toArray();

        return view('admin.maintenance_management.parts.parts_list.edit', compact('list', 'categories', 'parts', 'selectedPartIds'));
    }
}
