<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\PartsList;

use App\Models\MaintenanceManagement\Equipment;

use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

            $equipments = Equipment::get();

                 $categories = ProductCategory::with('products', 'equipments')->get();

         $query = Part::query()->with('category', 'templates',  'templates.category',
            'primarySupplier',
            'alt1Supplier',
            'alt2Supplier')->orderBy('part_name');

        // Search filter

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('part_name', 'like', "%{$search}%");
            });
        }

          // Category filter
        if ($request->filled('category')) {
            // $query->where('part_category_id', $request->category);
        }

          // Stock status filter 
        if ($request->filled('stock_status')) {
            $status = $request->stock_status;
            switch ($status) {
                case 'dni':
                    $query->where('dni', true);
                    break;
                case 'out-of-stock':
                    $query->where('dni', false)->where('stock_level', 0);
                    break;
                case 'buy-now':
                    $query->where('dni', false)->whereColumn('stock_level', '<', 'min_stock');
                    break;
                case 'in-stock':
                    $query->where('dni', false)->whereColumn('stock_level', '>=', 'min_stock');
                    break;
            }
        }

        //  Equipment filter
        if ($request->filled('equipment_id')) {
    $equipmentId = $request->equipment_id;

    $query->whereHas('partsLists', function ($q) use ($equipmentId) {
        $q->where(function ($subQuery) use ($equipmentId) {
            $subQuery->whereJsonContains('parts_lists.selected_products', (int)$equipmentId)
                     ->orWhereJsonContains('parts_lists.selected_products', strval($equipmentId));
        });
    });
}

       
      // Paginate parts
        $parts = $query->paginate(10)->withQueryString();


        // Part List 

        $partlistQuery = PartsList::with(['creator', 'category', 'parts'])->latest();

        if ($request->filled('tsearch')) {
            $partlistQuery->where('name', 'like', "%{$request->tsearch}%");
        }

        if ($request->filled('tcategory')) {
            $partlistQuery->where('category_id', $request->tcategory);
        }

        $partlists = $partlistQuery->paginate(10)->withQueryString();


        // ----- STOCK COUNTS -----
        $stockCounts = [
            'total'        => Part::count(),
            'in_stock'     => Part::where('dni', false)->whereColumn('stock_level', '>=', 'min_stock')->count(),
            'buy_now'      => Part::where('dni', false)->whereColumn('stock_level', '<', 'min_stock')->where('stock_level', '>', 0)->count(),
            'out_of_stock' => Part::where('dni', false)->where('stock_level', 0)->count(),
            'dni'          => Part::where('dni', true)->count(),
        ];

        // Part list 

        // AJAX partial update
        if ($request->ajax()) {
           // Detect which section the request is for
            if ($request->has('tsearch') || $request->has('tcategory')) {
                return response()->json([
                    'html' => view('admin.maintenance_management.parts.parts_list.partials._table', compact('partlists'))->render(),
                    'total' => $partlists->count(),
                ]);
            }

            return response()->json([
                'html' => view('admin.maintenance_management.parts.partials._table', compact('parts'))->render(),
                'total' => $parts->count(),
            ]);
        }

       return view('admin.maintenance_management.parts.index', compact('parts', 'partlists', 'categories', 'stockCounts', 'equipments'));


    }

}
