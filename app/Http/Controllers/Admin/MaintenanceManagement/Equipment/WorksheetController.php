<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\PartsList;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class WorksheetController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Equipment::query()->with(['productCategory', 'store']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = trim((string) $request->input('search'));
            $q->where(function ($sub) use ($search) {
                $sub->where('equipment_name', 'like', '%' . $search . '%')
                    ->orWhere('equipment_id', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%');
            });
        });

        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('product_category_id', $request->input('category'));
        });

        $query->when($request->filled('store'), function ($q) use ($request) {
            $q->where('store_id', $request->input('store'));
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('current_status', $request->input('status'));
        });

        $equipment = $query
            ->orderBy(ProductCategory::select('title')->whereColumn('product_categories.id', 'equipment.product_category_id'), 'asc')
            ->orderBy('equipment_name', 'asc')
            ->paginate(40)
            ->withQueryString();

        $categories = ProductCategory::getHierarchy();
        $stores = Store::active()->orderByAdmin()->pluck('store_name', 'id');
        $checklistMasters = ChecklistMaster::orderBy('checklist_system_name')->pluck('checklist_system_name', 'id');
        $partsLists = PartsList::orderBy('name')->pluck('name', 'id');

        return view(
            'admin.maintenance_management.equipment.worksheet',
            compact('equipment', 'categories', 'stores', 'checklistMasters', 'partsLists')
        );
    }
}
