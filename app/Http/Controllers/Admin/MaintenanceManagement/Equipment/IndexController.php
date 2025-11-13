<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // Service due filter
        // $query->when($request->serviceDue, function ($q, $serviceDue) {
        //     if ($serviceDue === 'due-soon') {
        //         $q->whereRaw('(current_hours % service_interval) / service_interval >= 0.8 AND (current_hours % service_interval) / service_interval < 1');
        //     } elseif ($serviceDue === 'overdue') {
        //         $q->whereRaw('(current_hours % service_interval) / service_interval >= 1');
        //     }
        // });

        // // Rental Ready filter
        // $query->when($request->rentalReady, function ($q, $rentalReady) {
        //     if ($rentalReady === 'assigned') {
        //         $q->whereNotNull('rental_ready_checklist');
        //     } elseif ($rentalReady === 'not-assigned') {
        //         $q->whereNull('rental_ready_checklist');
        //     }
        // });

        // // Equipment Service filter
        // $query->when($request->equipService, function ($q, $equipService) {
        //     if ($equipService === 'assigned') {
        //         $q->whereNotNull('equipment_service_list');
        //     } elseif ($equipService === 'not-assigned') {
        //         $q->whereNull('equipment_service_list');
        //     }
        // });

        $query = Equipment::with('productCategory', 'checklistMaster');

        // Checklist Master filter
        $query->when($request->checklist_master, function ($q, $checklistMaster) {
            if ($checklistMaster === 'assigned') {
                $q->whereNotNull('checklist_master_id');
            } elseif ($checklistMaster === 'Pending') {
                $q->whereNull('checklist_master_id');
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
            return view('admin.maintenance_management.equipment.partials._table', compact('equipment'))->render();
        }

        return view('admin.maintenance_management.equipment.index', compact('equipment', 'stats', 'categories'));
    }
}
