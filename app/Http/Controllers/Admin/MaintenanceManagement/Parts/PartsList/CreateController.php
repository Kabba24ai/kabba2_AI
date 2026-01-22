<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\Product;
use App\Models\MaintenanceManagement\Part;


class CreateController extends Controller
{
    public function __invoke()
    {

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

            // dd($categories);
        
        $parts = Part::with('category')->orderBy('part_name')->get();

        return view('admin.maintenance_management.parts.parts_list.create')->with('parts', $parts)->with('categories', $categories);
    }
}

