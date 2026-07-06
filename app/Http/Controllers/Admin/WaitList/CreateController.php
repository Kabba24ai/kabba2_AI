<?php

namespace App\Http\Controllers\Admin\WaitList;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;

class CreateController extends Controller
{
    public function __invoke()
    {
        // Structured selections only — CRM customers, categories, equipment, stores
        $customers = Customer::where('status', 'Active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'phone', 'email']);

        $categories = ProductCategory::published()->sortOrder()->get(['id', 'title']);
        $equipment  = Equipment::orderBy('equipment_name')->get(['id', 'equipment_name', 'equipment_id']);
        $stores     = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);

        return view('admin.wait_list.create', compact('customers', 'categories', 'equipment', 'stores'));
    }
}
