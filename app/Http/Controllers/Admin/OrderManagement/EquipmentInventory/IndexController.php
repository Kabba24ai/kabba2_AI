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
        $query = Equipment::with('statusUpdatedByUser', 'productCategory', 'order', 'order.customer', 'store', 'orderProduct','lastOrderProduct', 'activeEquipmentRentalReadyTemplate')
        ->when($request->filled('search'), function ($q) use ($request) {
            $q->where('equipment_name', 'like', '%' . $request->search . '%')
                ->orWhere('equipment_id', 'like', '%' . $request->search . '%')
                ->orWhereHas('order', function ($q) use ($request) {
                    $q->where('customer_name', 'like', '%' . $request->search . '%');
            });
        })
        ->when($request->filled('category'), function ($q) use ($request) {
            $q->where('product_category_id', $request->category);
        })
        ->when($request->filled('store'), function ($q) use ($request) {
            $q->whereHas('lastOrderProduct', function ($q) use ($request) {
                $q->where('pickup_store_id', $request->store);
            });
        })
        ->when($request->filled('equipment_status'), function ($q) use ($request) {
            $q->whereIn('current_status', $request->equipment_status);
        }, function ($q) {
            $q->whereIn('current_status', EquipmentCurrentStatus::getValues());
        });

        $order = ['damaged', 'maintenance', 'rented', 'available'];
        $query
            ->leftJoin('product_categories', 'product_categories.id', '=', 'equipment.product_category_id')
            ->select('equipment.*') // keep equipment columns
            ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
            ->orderBy('product_categories.title', 'asc')
            ->orderBy('equipment_name', 'asc')
            ->orderBy('equipment_id', 'asc');
        $perPage = $request->input('per_page', 10);
        $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
        $equipment = $query->paginate($perPageVal)->withQueryString();

        $categories = ProductCategory::getHierarchy();

        $stores = Store::all();

        // Pass enum values to the view
        $statuses = EquipmentCurrentStatus::cases(); // returns all enum cases

        if ($request->ajax()) {
            $html = view('admin.order_management.equipment_inventory.partials._table', compact('equipment'))->render();
            return response()->json([
                'html' => $html,
                'total' => $equipment->count(),
            ]);
        }

        return view('admin.order_management.equipment_inventory.index', compact('equipment', 'categories', 'stores', 'statuses'));
    }
}
