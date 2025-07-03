<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Part::query()->orderBy('part_name');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('part_name', 'like', "%{$search}%")
                  ->orWhere('part_number', 'like', "%{$search}%")
                  ->orWhere('equipment_name', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Equipment filter
        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
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

        $parts = $query->get();

        $categories = Part::distinct()->pluck('category')->sort();
        $equipmentOptions = Part::select('equipment_id', 'equipment_name')
                                ->distinct()
                                ->orderBy('equipment_name')
                                ->get();
        if ($request->ajax()) {
            return view('admin.maintenance_management.parts.partials._table', compact('parts'))->render();
        }
        return view('admin.maintenance_management.parts.index', compact('parts', 'categories', 'equipmentOptions'));
    }
}
