<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleConflicts;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\Orders\ScheduleConflictService;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request, ScheduleConflictService $conflicts)
    {
        // ── Filter inputs ─────────────────────────────────────────────────────
        $search   = trim((string) $request->input('search', ''));
        $category = $request->input('category', '');   // product_category_id on equipment
        $store    = $request->input('store', '');       // store_id on equipment
        $section  = $request->input('section', '');     // which conflict type to show

        // ── Dropdown data ─────────────────────────────────────────────────────
        $categories = ProductCategory::orderBy('title')->pluck('title', 'id');
        $stores     = Store::orderBy('store_name')->pluck('store_name', 'id');

        // Canonical conflict aggregation — the same service the Dashboard card
        // consumes, so the two surfaces always report the same totals.
        $result = $conflicts->build(compact('search', 'category', 'store', 'section'));

        $doubleBookings             = $result['doubleBookings'];
        $backToBackAlerts           = $result['backToBackAlerts'];
        $damagedBookings            = $result['damagedBookings'];
        $overdueEquipmentConflicts  = $result['overdueEquipmentConflicts'];
        $noDirectAssignmentGroups   = $result['noDirectAssignmentGroups'];
        $inventoryLocationConflicts = $result['inventoryLocationConflicts'];
        $totalConflicts             = $result['total'];

        $employees = User::active()
            ->orderBy('first_name')
            ->get()
            ->pluck('full_name', 'unique_id');

        $callUsers = User::active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone', 'email']);

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);

        return view('admin.order_management.schedule_conflicts.index', compact(
            'doubleBookings',
            'backToBackAlerts',
            'damagedBookings',
            'noDirectAssignmentGroups',
            'overdueEquipmentConflicts',
            'inventoryLocationConflicts',
            'totalConflicts',
            'employees',
            'categories',
            'stores',
            'search',
            'category',
            'store',
            'section',
            'callUsers',
            'customers',
            'suppliers',
        ));
    }
}
