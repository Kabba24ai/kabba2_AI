<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

        $query = Equipment::with('statusUpdatedByUser', 'productCategory', 'checklistMaster', 'store', 'order.customer','activeEquipmentRentalReadyTemplate');
$equipmentIds = Equipment::orderByRaw('CAST(equipment_id AS CHAR) ASC')
    ->pluck('equipment_id');

        // Checklist Master filter
        $query->when($request->checklist_master, function ($q, $checklistMaster) {
            if ($checklistMaster === 'assigned') {
                $q->whereNotNull('checklist_master_id');
            } elseif ($checklistMaster === 'Pending') {
                $q->whereNull('checklist_master_id');
            }
        });

        // Checklist Master filter
        $query->when($request->location_store, function ($q, $locationstore) {
            if ($locationstore === 'assigned') {
                $q->whereNotNull('store_id');
            } elseif ($locationstore === 'Pending') {
                $q->whereNull('store_id');
            }
        });

        // status filter
        $query->when($request->status, function ($q, $status) {
            $q->where('current_status', $status);
        });

        // Search filter
        $query->when($request->search, function ($q, $search) {
            $q->where('equipment_name', 'like', '%' . $search . '%');
        });

$query->when($request->equipment_id, function ($q, $equipmentId) {
    $q->where('equipment_id', $equipmentId);
});


        // Category filter
        $query->when($request->category, function ($q, $category) {
            $q->where('product_category_id', $category);
        });

        

        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
        $equipment = $query
            ->orderBy(ProductCategory::select('title')->whereColumn('product_categories.id', 'equipment.product_category_id'), 'asc')
            ->orderBy('equipment_name', 'asc')
            ->orderBy('equipment_id', 'asc')
            ->paginate($perPageVal)
            ->withQueryString();

        $stores = Store::active()->pluck('store_name', 'unique_id');

        // Calculate stats
        $stats = [
            'total' => Equipment::count(),
            'available' => Equipment::where('current_status', 'available')->count(),
            'rented' => Equipment::where('current_status', 'rented')->count(),
            'maintenance' => Equipment::where('current_status', 'maintenance')->count(),
            'damaged' => Equipment::where('current_status', 'damaged')->count(),
        ];

        $categories = ProductCategory::getHierarchy();

        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            $html = view('admin.maintenance_management.equipment.partials._table', compact('equipment'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        return view('admin.maintenance_management.equipment.index', compact( 'stats', 'categories', 'stores','equipmentIds'));
    }
}
