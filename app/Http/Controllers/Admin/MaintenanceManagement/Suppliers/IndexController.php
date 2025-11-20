<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locations\State;

use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\SupplierTag;



class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // Example supplier data
        $query = Supplier::with(['state', 'category', 'media']);


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
        if ($request->filled('part_search')) {

        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('supplier_category_id', $request->category);
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



        if ($request->ajax()) {
            $tableView = view('admin.maintenance_management.suppliers.partials._table', compact('suppliers'))->render();
            return response()->json([
                'html' => $tableView,
                'total' => $suppliers->total(),
            ]);
        }

        return view('admin.maintenance_management.suppliers.index', compact('suppliers', 'states'));
    }
}
