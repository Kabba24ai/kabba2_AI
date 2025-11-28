<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locations\State;

use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\SupplierTag;
use App\Models\MaintenanceManagement\Part;
use App\Models\ProductManagement\ProductCategory;



class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

        $parts = Part::orderBy('part_name')->get();


        // Example supplier data
        $query = Supplier::with([    'primaryParts.partsLists.category',
    'alt1Parts.partsLists.category',
    'alt2Parts.partsLists.category', 'state', 'category', 'media']);


        // Search by name/email
        if ($request->filled('search_name_email')) {
            $query->where(function ($q) use ($request) {
                $q->where('primary_contact_name', 'like', "%{$request->search_name_email}%")
                    ->orWhere('email', 'like', "%{$request->search_name_email}%")
                    ->orWhere('inside_sales_name', 'like', "%{$request->search_name_email}%");
            });
        }


        // Search by company
        if ($request->filled('company_search')) {
            $query->where('name', 'like', "%{$request->company_search}%");
        }


        // Search by tags
        if ($request->filled('tags_search')) {
            $tagNames = array_map('trim', explode(',', $request->tags_search));
            $tagIds = \App\Models\MaintenanceManagement\SupplierTag::whereIn('name', $tagNames)->pluck('id')->toArray();

            if (!empty($tagIds)) {
                $query->where(function ($q) use ($tagIds) {
                    foreach ($tagIds as $tagId) {
                        $q->orWhereRaw('FIND_IN_SET(?, tags)', [$tagId]);
                    }
                });
            }
        }



        // Search by part
        // Search by part name (primary / alt1 / alt2 supplier parts)
if ($request->filled('part_search')) {
    $searchTerm = $request->part_search;

    $query->where(function ($q) use ($searchTerm) {
        $q->whereHas('primaryParts', function ($sub) use ($searchTerm) {
            $sub->where('part_name', 'like', "%{$searchTerm}%");
        })
        ->orWhereHas('alt1Parts', function ($sub) use ($searchTerm) {
            $sub->where('part_name', 'like', "%{$searchTerm}%");
        })
        ->orWhereHas('alt2Parts', function ($sub) use ($searchTerm) {
            $sub->where('part_name', 'like', "%{$searchTerm}%");
        });
    });
}

        // Category filter
        // Filter by PartsList Category of any supplied Part
if ($request->filled('category')) {
    $category = $request->category;

    $query->where(function ($q) use ($category) {
        $q->whereHas('primaryParts.partsLists', function ($sub) use ($category) {
            $sub->where('category_id', $category);
        })
        ->orWhereHas('alt1Parts.partsLists', function ($sub) use ($category) {
            $sub->where('category_id', $category);
        })
        ->orWhereHas('alt2Parts.partsLists', function ($sub) use ($category) {
            $sub->where('category_id', $category);
        });
    });
}

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', ucfirst($request->status));
        }

        // Always sort alphabetically by supplier name
        $query->orderBy('name', 'ASC');

        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

        $suppliers = $query->latest()->paginate($perPageVal)->withQueryString();

        // Append tag objects
        $suppliers->each(function ($supplier) {
            $supplier->tag_objects = $supplier->tag_objects;
        });



        $states = State::get();

        // dd($parts);
                 $categories = ProductCategory::with('products', 'equipments')->get();


        if ($request->ajax()) {
            $tableView = view('admin.maintenance_management.suppliers.partials._table', compact('suppliers'))->render();
            return response()->json([
                'html' => $tableView,
                'total' => $suppliers->total(),
            ]);
        }

        // dd( $suppliers->first()->all_supplied_parts);

        return view('admin.maintenance_management.suppliers.index', compact('suppliers', 'states','parts','categories'));
    }
}
