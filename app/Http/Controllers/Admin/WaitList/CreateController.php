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
        // Structured selections only — CRM customers, categories, equipment units, stores
        $customers = Customer::where('status', 'Active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'company_name', 'phone', 'email']);

        $categories = ProductCategory::published()->sortOrder()->get(['id', 'title']);
        $stores     = Store::where('status', 'Active')->orderBy('store_name')->get(['id', 'store_name']);

        // ACTUAL equipment inventory units with their category, status, and
        // location; the form filters this list client-side as the category
        // changes. The employee selects individual assets by Equipment ID —
        // two units of the same product are two separate rows. Sorted by
        // equipment name, then Equipment ID.
        $equipmentOptions = Equipment::query()
            ->where('not_for_rent', 0)
            ->with('store:id,store_name')
            ->orderBy('equipment_name')
            ->orderBy('equipment_id')
            ->get(['id', 'equipment_id', 'equipment_name', 'product_category_id', 'current_status', 'store_id'])
            ->map(fn ($unit) => [
                'id'          => $unit->id,
                'code'        => $unit->equipment_id,
                'name'        => $unit->equipment_name,
                'category_id' => $unit->product_category_id,
                'status'      => $unit->current_status?->label() ?? 'Unknown',
                'store'       => $unit->store?->store_name,
            ])
            ->values();

        return view('admin.wait_list.create', compact('customers', 'categories', 'stores', 'equipmentOptions'));
    }
}
