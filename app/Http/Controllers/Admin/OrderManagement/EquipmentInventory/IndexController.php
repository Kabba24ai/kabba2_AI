<?php

namespace App\Http\Controllers\Admin\OrderManagement\EquipmentInventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;

// Models
use App\Models\Orders\Order;
use App\Models\Stores\Store;
use App\Models\ProductManagement\ProductCategory;
use App\Enums\Equipments\EquipmentCurrentStatus;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Equipment::with('productCategory', 'order', 'orderProduct.deliveryStore', 'activeEquipmentRentalReadyTemplate');

        // Search filter
        if ($request->filled('search')) {
            $query
                ->where('equipment_name', 'like', '%' . $request->search . '%')
                ->orWhere('equipment_id', 'like', '%' . $request->search . '%')
                ->orWhereHas('order', function ($q) use ($request) {
                    $q->where('customer_name', 'like', '%' . $request->search . '%');
                });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('product_category_id', $request->category);
        }

        // Status filter
        // if ($request->filled('status')) {
        //     $query->where('current_status', $request->status);
        // }

        // Store filter
        if ($request->filled('store')) {
            // $query->whereHas('orderProduct.deliveryStore', function ($q) use ($request) {
            //     $q->where('id', $request->store);
            // });
        }

        // Equipment Status checkboxes
        if ($request->filled('equipment_status')) {
            $query->whereIn('current_status', $request->equipment_status);
        } else {
            // default: show all statuses
            $query->whereIn('current_status', EquipmentCurrentStatus::getValues());
        }

        $order = ['damaged', 'maintenance', 'rented', 'available'];
        $equipment = $query
            ->leftJoin('product_categories', 'product_categories.id', '=', 'equipment.product_category_id')
            ->select('equipment.*') // keep equipment columns
            ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
            ->orderBy('product_categories.title', 'asc')
            ->orderBy('equipment_name', 'asc')
            ->orderBy('equipment_id', 'asc')
            ->get();

        $categories = ProductCategory::getHierarchy();

        $stores = Store::all();

        // Pass enum values to the view
        $statuses = EquipmentCurrentStatus::cases(); // returns all enum cases

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.order_management.equipment_inventory.partials._table', compact('equipment', 'stores'))->render(),
                'total' => $equipment->count(),
            ]);
        }

        return view('admin.order_management.equipment_inventory.index', compact('equipment', 'categories', 'stores', 'statuses'));
    }
}
