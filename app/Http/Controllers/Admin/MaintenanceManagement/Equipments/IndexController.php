<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipments;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
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
        
        // Status filter
        // if ($request->filled('status')) {
        //     $query->where('status', $request->status);
        // }
        
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
        
        $equipments = $query->orderBy('created_at', 'desc')->paginate(50);
        
        // Calculate stats
        $stats = [
            'total' => Equipment::count(),
            'available' => Equipment::where('status', 'available')->count(),
            'rented' => Equipment::where('status', 'rented')->count(),
            'maintenance' => Equipment::where('status', 'maintenance')->count(),
            'damaged' => Equipment::where('status', 'damaged')->count(),
        ];
        
        $categories = Equipment::distinct()->pluck('category')->filter()->values();
        
        // Return only the table partial if it's an AJAX request
        if ($request->ajax()) {
            return view('admin.maintenance_management.equipments.partials._table', compact('equipments'))->render();
        }
        
        return view('admin.maintenance_management.equipments.index', compact('equipments', 'stats', 'categories'));
    }
}
