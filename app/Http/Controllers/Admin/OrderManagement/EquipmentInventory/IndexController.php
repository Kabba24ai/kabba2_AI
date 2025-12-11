<?php

namespace App\Http\Controllers\Admin\OrderManagement\EquipmentInventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;

// Models
use App\Models\Stores\Store;
use App\Models\ProductManagement\ProductCategory;
use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

        $categories = ProductCategory::getHierarchy();

        $stores = Store::all();

        // Pass enum values to the view
        $statuses = EquipmentCurrentStatus::cases(); // returns all enum cases

        $startDate = now();
        $endDate = $startDate->copy()->addDays(13); // 14 days total (2 weeks)
        $dates = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dates[] = $date->copy();
        }

        if ($request->ajax()) {
            $query = Equipment::with('statusUpdatedByUser', 'productCategory', 'order', 'order.customer', 'store', 'orderProduct', 'lastOrderProduct', 'activeEquipmentRentalReadyTemplate')
            ->where('not_for_rent', 0)
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $q->where(function ($sub) use ($request) {
                            $sub->where('equipment_name', 'like', '%' . $request->search . '%')
                                ->orWhere('equipment_id', 'like', '%' . $request->search . '%')
                                ->orWhereHas('order', function ($orderQ) use ($request) {
                                    $orderQ->where('customer_name', 'like', '%' . $request->search . '%');
                                });
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
                    ->when(
                        $request->filled('equipment_status'),
                        function ($q) use ($request) {
                            $q->whereIn('current_status', $request->equipment_status);
                        },
                        function ($q) {
                            $q->whereIn('current_status', EquipmentCurrentStatus::getValues());
                        },
                    );

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
            $equipment = $query->paginate(max(1, $query->count()))->withQueryString();


            $html = view('admin.order_management.equipment_inventory.partials._table', compact('equipment', 'dates'))->render();
            return response()->json([
                'html' => $html,
                'total' => $equipment->count(),
            ]);
        }

        $query2 = OrderProduct::query()
            ->with('equipment', 'equipment.productcategory', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.lastPayment')
            ->where('product_data->product_type', 'Rental')
            ->whereNotNull('delivery_date')
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('delivery_status', '!=', 'Completed')->orWhere('pickup_status', '!=', 'Completed');
                })->whereNot(function ($subQ) {
                    $subQ->where('delivery_status', 'Completed')->where('pickup_status', 'Completed');
                });
            })
            ->whereDoesntHave('softAssignment')
            ->whereDoesntHave('equipment');

        $orderProducts = $query2
            //->whereBetween('order_id', [100, 130])
            ->orderBy('delivery_date', 'asc')
            ->paginate(max(1, $query2->count()))
            ->withQueryString();

        $users = User::orderBy('first_name', 'asc')
            ->get()
            ->map(function ($user) {
                return [
                    'unique_id' => $user->unique_id,
                    'full_name' => $user->full_name,
                ];
            });

        $employees = $users->pluck('full_name', 'unique_id')->prepend('Select Employee', '');

        return view('admin.order_management.equipment_inventory.index', compact('categories', 'stores', 'statuses', 'dates', 'orderProducts', 'employees'));
    }
}
