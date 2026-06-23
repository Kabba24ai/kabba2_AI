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
        $storesForModal = Store::active()->orderByAdmin()->pluck('store_name', 'unique_id');

        // Pass enum values to the view
        $statuses = EquipmentCurrentStatus::cases(); // returns all enum cases

        $startDate = now();
        $endDate = $startDate->copy()->addDays(13); // 14 days total (2 weeks)
        $dates = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dates[] = $date->copy();
        }

        if ($request->ajax()) {
            if ($request->input('view_mode') === 'store') {
                $storeQuery = Equipment::with(['productCategory', 'store'])
                    ->where('not_for_rent', 0)
                    ->where('current_status', '!=', 'rented')
                    ->whereNotNull('store_id')
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $q->where(function ($sub) use ($request) {
                            $sub->where('equipment_name', 'like', '%' . $request->search . '%')
                                ->orWhere('equipment_id', 'like', '%' . $request->search . '%');
                        });
                    })
                    ->when($request->filled('category'), function ($q) use ($request) {
                        $q->where('product_category_id', $request->category);
                    })
                    ->when(
                        $request->filled('equipment_status'),
                        fn($q) => $q->whereIn('current_status', $request->equipment_status),
                        fn($q) => $q->whereIn('current_status', EquipmentCurrentStatus::getValues()),
                    )
                    ->when($request->boolean('currently_assigned'), function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereHas('orderProducts', function ($sub) {
                                $sub->where(function ($s) {
                                    $s->where('delivery_status', 'Pending')
                                      ->orWhere('pickup_status', 'Pending');
                                });
                            })
                            ->orWhereHas('softAssignments.orderProduct', function ($sub) {
                                $sub->where(function ($s) {
                                    $s->where('delivery_status', 'Pending')
                                      ->orWhere('pickup_status', 'Pending');
                                });
                            });
                        });
                    })
                    ->leftJoin('product_categories', 'product_categories.id', '=', 'equipment.product_category_id')
                    ->select('equipment.*')
                    ->orderBy('product_categories.title', 'asc')
                    ->orderBy('equipment_name', 'asc')
                    ->orderBy('equipment_id', 'asc');

                $allEquipment    = $storeQuery->get();
                $storeIds        = $allEquipment->pluck('store_id')->unique()->filter();
                $storeViewStores = Store::whereIn('id', $storeIds)->orderBy('store_name')->get();

                $html = view('admin.order_management.equipment_inventory.partials._store_view', [
                    'allEquipment' => $allEquipment,
                    'stores'       => $storeViewStores,
                ])->render();

                return response()->json([
                    'html'  => $html,
                    'total' => $allEquipment->count(),
                ]);
            }

            $query = Equipment::with([
                'statusUpdatedByUser',
                'productCategory',
                'order',
                'order.customer',
                'store',
                'activeEquipmentRentalReadyTemplate',
                'lastCompletedOrderProduct.order',
                'nextAssignedOrderProduct.order',
                'softAssignments',
                'softAssignments.order',
                'softAssignments.orderProduct' => function ($q) {
                    $q->whereHas('order');
                },
                'softAssignments.orderProduct.order',
            ])
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
                        // Rented equipment is physically at the customer site, not at any store.
                        // Exclude it from all store-scoped queries so the filter matches what
                        // the Location column displays (store name vs customer name).
                        $q->where('current_status', '!=', 'rented');
                        if ($request->store === 'all_stores') {
                            $q->whereNotNull('store_id');
                        } else {
                            $q->where('store_id', $request->store);
                        }
                    })
                    ->when(
                        $request->filled('equipment_status'),
                        function ($q) use ($request) {
                            $q->whereIn('current_status', $request->equipment_status);
                        },
                        function ($q) {
                            $q->whereIn('current_status', EquipmentCurrentStatus::getValues());
                        },
                    )
                    ->when($request->boolean('currently_assigned'), function ($q) {
                        $q->where(function ($sq) {
                            // Hard assignment: pending delivery or pickup
                            $sq->whereHas('orderProducts', function ($sub) {
                                $sub->where(function ($s) {
                                    $s->where('delivery_status', 'Pending')
                                      ->orWhere('pickup_status', 'Pending');
                                });
                            })
                            // Soft assignment: soft-assigned to a pending order product
                            ->orWhereHas('softAssignments.orderProduct', function ($sub) {
                                $sub->where(function ($s) {
                                    $s->where('delivery_status', 'Pending')
                                      ->orWhere('pickup_status', 'Pending');
                                });
                            });
                        });
                    });

            $order = ['damaged', 'maintenance', 'rented', 'available'];
            $query
                ->leftJoin('product_categories', 'product_categories.id', '=', 'equipment.product_category_id')
                ->select('equipment.*') // keep equipment columns
                ->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
                ->orderBy('product_categories.title', 'asc')
                ->orderBy('equipment_name', 'asc')
                ->orderBy('equipment_id', 'asc');

            $perPage = $request->input('per_page', 30);

            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $equipment = $query->paginate($perPageVal)->withQueryString();


            $html = view('admin.order_management.equipment_inventory.partials._table', compact('equipment', 'dates'))->render();
            return response()->json([
                'html' => $html,
                'total' => $equipment->count(),
            ]);
        }

        return view('admin.order_management.equipment_inventory.index', compact('categories', 'stores', 'storesForModal', 'statuses', 'dates'));
    }
}
