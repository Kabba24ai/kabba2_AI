<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

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


        if ($request->filled('past_seven_days') && $request->past_seven_days === '1') {
            $startDate = now()->subDays(6); // 7 days total (including today)
            $endDate = now();
        }else{
            $startDate = now();
            $endDate = $startDate->copy()->addDays(13); // 14 days total (2 weeks)
        }
        $dates = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dates[] = $date->copy();
        }
        if ($request->ajax()) {

            $query = Equipment::with('statusUpdatedByUser', 'productCategory', 'order', 'order.customer', 'store', 'orderProduct', 'lastOrderProduct', 'activeEquipmentRentalReadyTemplate', 'softAssignments' , 'softAssignments.order')
            ->where('not_for_rent', 0)
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $search = $request->search;
                        $q->where(function ($sub) use ($search) {
                            $sub->where('equipment_name', 'like', "%{$search}%")
                                ->orWhere('equipment_id', 'like', "%{$search}%")
                                ->orWhereHas('order', function ($orderQ) use ($search) {
                                    $orderQ->where('customer_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('softAssignments', function ($saQ) use ($search) {
                                    $saQ->whereHas('order', function ($orderQ2) use ($search) {
                                        $orderQ2->where('customer_name', 'like', "%{$search}%");
                                    });
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
                    )
                    ->when($request->filled('assignment_filter'), function ($q) use ($request, $startDate, $endDate) {
                        $filter = $request->assignment_filter;

                        if ($filter == 'assigned') {
                            $q->where(function ($subQ) use ($startDate, $endDate) {

                                // HARD assignment: lastOrderProduct (or orderProduct) in current window
                                $subQ->whereHas('lastOrderProduct', function ($lop) use ($startDate, $endDate) {
                                    $lop->whereDate('delivery_date', '<=', $endDate)
                                        ->whereDate('pickup_date',   '>=', $startDate);
                                })
                                ->orWhereHas('softAssignments.orderProduct', function ($op) use ($startDate, $endDate) {
                                    $op->whereDate('delivery_date', '<=', $endDate)
                                    ->whereDate('pickup_date',   '>=', $startDate);
                                });

                            });
                        } elseif ($filter == 'assigned_3_days') {

                            $q->where(function ($subQ) use ($startDate, $endDate) {

                                $subQ->whereHas('lastOrderProduct', function ($lop) use ($startDate, $endDate) {
                                    $lop->where(function ($d) use ($startDate, $endDate) {
                                            $d->whereBetween('delivery_date', [$startDate, $endDate])
                                            ->orWhereBetween('pickup_date',   [$startDate, $endDate]);
                                        })
                                        ->whereNotNull('delivery_date')
                                        ->whereNotNull('pickup_date')
                                        ->whereRaw("DATEDIFF(pickup_date, delivery_date) = 2");
                                })
                                ->orWhereHas('softAssignments.orderProduct', function ($op) use ($startDate, $endDate) {
                                    $op->where(function ($d) use ($startDate, $endDate) {
                                            $d->whereBetween('delivery_date', [$startDate, $endDate])
                                            ->orWhereBetween('pickup_date',   [$startDate, $endDate]);
                                        })
                                        ->whereNotNull('delivery_date')
                                        ->whereNotNull('pickup_date')
                                        ->whereRaw("DATEDIFF(pickup_date, delivery_date) = 2");
                                });
                            });
                        }
                    });

            $order = ['damaged', 'maintenance', 'rented', 'available'];
            $query
                ->leftJoin('product_categories', 'product_categories.id', '=', 'equipment.product_category_id')
                ->select('equipment.*') // keep equipment columns
                //->orderByRaw("FIELD(current_status, '" . implode("','", $order) . "')")
                ->orderBy('product_categories.title', 'asc')
                ->orderBy('equipment_name', 'asc')
                ->orderBy('equipment_id', 'asc');
            $perPage = $request->input('per_page', 10);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $equipment = $query->paginate(max(1, $query->count()))->withQueryString();


            $html = view('admin.order_management.schedule_assignment.partials._table', compact('equipment', 'dates'))->render();
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

        return view('admin.order_management.schedule_assignment.index', compact('categories', 'stores', 'dates', 'statuses', 'orderProducts', 'employees'));
    }
}
