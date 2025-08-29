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
        $query = Equipment::query();

        // Search filter
        if ($request->filled('search')) {
            $query->where('equipment_name', 'like', '%' . $request->search . '%');
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Service due filter
        if ($request->filled('serviceDue')) {
            if ($request->serviceDue === 'due-soon') {
                $query->whereRaw('(current_hours % service_interval) / service_interval >= 0.8 AND (current_hours % service_interval) / service_interval < 1');
            } elseif ($request->serviceDue === 'overdue') {
                $query->whereRaw('(current_hours % service_interval) / service_interval >= 1');
            }
        }

        // Rental Ready filter
        if ($request->filled('rentalReady')) {
            if ($request->rentalReady === 'assigned') {
                $query->whereNotNull('rental_ready_checklist');
            } elseif ($request->rentalReady === 'not-assigned') {
                $query->whereNull('rental_ready_checklist');
            }
        }

        // Equipment Service filter
        if ($request->filled('equipService')) {
            if ($request->equipService === 'assigned') {
                $query->whereNotNull('equipment_service_list');
            } elseif ($request->equipService === 'not-assigned') {
                $query->whereNull('equipment_service_list');
            }
        }

        $equipment = $query->latest('id')->paginate(10);

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
