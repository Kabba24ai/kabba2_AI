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

            $allpartlists = PartsList::get();

                 $categories = ProductCategory::with('products', 'equipments')->get();

         $query = Part::query()->with('category', 'templates',  'templates.category',
          'partsLists',
          'partsLists.parts',
        'partsLists.category',
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


        // Category filter (PartsList Category)
if ($request->filled('category')) {
    $categoryId = $request->category;

    $query->whereHas('partsLists', function ($q) use ($categoryId) {
        $q->where('category_id', $categoryId);
    });
}

if ($request->filled('partlist')) {
    $partlistId = $request->partlist;

    $query->whereHas('partsLists', function ($q) use ($partlistId) {
        $q->where('parts_lists.id', $partlistId); // <-- Prefix table name
    });
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

      // Equipment filter
if ($request->filled('equipment_id')) {
    $equipmentId = $request->equipment_id;

    $query->whereHas('partsLists', function ($q) use ($equipmentId) {
        // Check both string and integer versions
        $q->whereJsonContains('selected_products', (int)$equipmentId)
          ->orWhereJsonContains('selected_products', (string)$equipmentId);
    });
}



      // Paginate parts
      $perPage = $request->input('per_page', 10);
$perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

$parts = $query->paginate($perPageVal)->withQueryString();

        // $parts = $query->paginate(10)->withQueryString();


        // Part List

        $partlistQuery = PartsList::with(['creator', 'category', 'parts'])->latest();

        if ($request->filled('tsearch')) {
            $partlistQuery->where('name', 'like', "%{$request->tsearch}%");
        }

        if ($request->filled('tcategory')) {
            $partlistQuery->where('category_id', $request->tcategory);
        }

// Part list pagination
$tplPerPage = $request->input('tpl_per_page', 10);
$tplPerPageVal = $tplPerPage === 'all' ? max(1, $partlistQuery->count()) : (int)$tplPerPage;
$tplPage = $request->input('tpl_page', 1);

$partlists = $partlistQuery->paginate($tplPerPageVal, ['*'], 'tpl_page', $tplPage);



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
if ($request->has('tsearch') || $request->has('tcategory') || $request->has('tpl_per_page') || $request->has('tpl_page')) {
                return response()->json([
                    'html' => view('admin.maintenance_management.parts.parts_list.partials._table', compact('partlists'))->render(),
                    'total' => $partlists->total(),

                ]);
            }

          return response()->json([
            'html' => view('admin.maintenance_management.parts.partials._table', compact('parts'))->render(),
            'total' => $parts->total(),
          ]);

        }

        // dd($parts->first());

       return view('admin.maintenance_management.parts.index', compact( 'partlists', 'categories', 'stockCounts', 'equipments','allpartlists'));

    }

}
