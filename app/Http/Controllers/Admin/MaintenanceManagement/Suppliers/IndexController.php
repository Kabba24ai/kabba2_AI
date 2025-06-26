<?php



namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Supplier::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Product search would require integration with parts system
        if ($request->filled('product_search')) {
            // This would need to be implemented based on your parts structure
            // $query->whereHas('parts', function($q) use ($request) {
            //     $q->where('category', 'like', "%{$request->product_search}%");
            // });
        }

        $suppliers = $query->orderBy('name')->paginate(20);

        // Statistics
        $stats = [
            'total_suppliers' => Supplier::count(),
            'active_suppliers' => Supplier::active()->count(),
            'inactive_suppliers' => Supplier::where('status', 'Inactive')->count(),
            'total_parts' => 0, // Would be calculated from parts relationships
        ];

        if ($request->ajax()) {
            return view('admin.maintenance_management.suppliers.partials._table', compact('suppliers'))->render();
        }
        return view('admin.maintenance_management.suppliers.index', compact('suppliers', 'stats'));
    }
}
