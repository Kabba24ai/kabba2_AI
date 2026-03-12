<?php

namespace App\Http\Controllers\Admin\OrderManagement\Schedules;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Http\Request;

// Models
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {
            // Fetch real product-wise order data
            $query = OrderProduct::query()
                ->with('equipment', 'equipment.productcategory', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.lastPayment', 'order.notes')
                ->where('product_data->product_type', 'Rental')
                ->whereNotNull('delivery_date');


            if ($request->filled('unassigned_equipment')) {
                $query->whereDoesntHave('softAssignment')->whereDoesntHave('equipment');
                $query->where(function ($q)  {
                    $q->where('delivery_status', 'Pending')->orWhere('pickup_status', 'Pending');
                });
            }

            if ($request->filled('order_number')) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('order_number', 'like', '%' . $request->order_number . '%')
                        ->orWhere('reference_order_number', 'like', '%' . $request->order_number . '%');

                });
            }

            if ($request->filled('customer_name')) {
                $query->whereHas('order.shippingAddress', function ($q) use ($request) {
                    $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$request->customer_name}%"]);
                });
            }

            if ($request->filled('customer_company_name')) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('company_name', 'like', '%' . $request->customer_company_name . '%');
                });
            }

            if ($request->filled('customer_phone')) {
                $query->whereHas('order.shippingAddress', function ($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->customer_phone . '%');
                });
            }

            if ($request->filled('category')) {
                $query->whereHas('product.categories', function ($q) use ($request) {
                    $q->where('product_categories.id', $request->category);
                });
            }

            if ($request->filled('payment_method') && $request->payment_method != 'All Methods') {
                $query->whereHas('order.lastPayment', function ($q) use ($request) {
                    $q->where('payment_method', $request->payment_method);
                });
            }

            if ($request->filled('payment_status') && $request->payment_status != 'All Status') {
                $query->whereHas('order.lastPayment', function ($q) use ($request) {
                    $q->where('status', $request->payment_status);
                });
            }

            // Filter: SCHEDULE TYPE + TRANSPORT MODE
            $scheduleTypes = [];
            $transportModes = [];
            $orderByField = 'delivery_date'; // Default order by field

            // Get filters, clean them
            if ($request->filled('schedule_type')) {
                $scheduleTypes = array_filter((array) $request->input('schedule_type', []), fn($v) => $v !== '' && $v !== 'false');
                $isReturnOnly = in_array('Return', $scheduleTypes) && !in_array('Delivery', $scheduleTypes);

                $query->where(function ($q) use ($scheduleTypes, $isReturnOnly) {
                    if (in_array('Delivery', $scheduleTypes)) {
                        $q->where('delivery_status', 'Pending');
                    }
                    if (in_array('Return', $scheduleTypes)) {
                        if ($isReturnOnly) {
                            $q->where('pickup_status', 'Pending')
                                ->where('delivery_status', 'Completed');
                        } else {
                            $q->orWhere('pickup_status', 'Pending');
                        }
                    }
                });
            }

            if ($request->filled('transport_mode')) {
                $transportModes = array_filter((array) $request->input('transport_mode', []), fn($v) => $v !== '' && $v !== 'false');
            }

            if (in_array('Return', $scheduleTypes) && !in_array('Delivery', $scheduleTypes)) {
                $orderByField = 'pickup_date';
            }

            if ($request->filled('date_filter')) {
                $dateFilter = $request->date_filter;
                if ($dateFilter === 'today') {
                    $query->whereDate($orderByField, today());
                } elseif ($dateFilter === 'week') {
                    $query->whereBetween($orderByField, [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($dateFilter === 'month') {
                    $query->whereMonth($orderByField, now()->month);
                }
            }

            // If no schedule type, fallback to original logic
            if (empty($scheduleTypes)) {
                // No schedule type, handle transport mode as before
                if (!empty($transportModes)) {
                    $query->where(function ($q) use ($transportModes) {
                        $q->whereIn('delivery_transport_mode', $transportModes)->orWhereIn('pickup_transport_mode', $transportModes);
                    });
                } else {
                    $query->where(function ($q) {
                        $q->whereIn('delivery_transport_mode', ['Truck', 'Store'])->orWhereIn('pickup_transport_mode', ['Truck', 'Store']);
                    });
                }
            } else {
                // If schedule type is filtered
                $query->where(function ($q) use ($scheduleTypes, $transportModes, &$orderByField) {
                    if (in_array('Delivery', $scheduleTypes) && !empty($transportModes)) {
                        // Filter delivery transport mode if given
                        $q->whereIn('delivery_transport_mode', $transportModes);
                    }
                    // Return
                    if (in_array('Return', $scheduleTypes) && !empty($transportModes)) {
                        // Filter pickup transport mode if given
                        $q->orWhereIn('pickup_transport_mode', $transportModes);
                    }
                });
            }

            if ($request->filled('store_location')) {
                $storeLocations = array_filter((array) $request->input('store_location', []), function ($v) {
                    return $v !== '' && $v !== 'false';
                });

                if (!empty($scheduleTypes)) {
                    $query->where(function ($q) use ($scheduleTypes, $storeLocations) {
                        if (in_array('Delivery', $scheduleTypes)) {
                            // For deliveries, filter delivery_store_id
                            if (!empty($storeLocations)) {
                                $q->orWhereIn('delivery_store_id', $storeLocations);
                            } else {
                                $q->orWhereNull('delivery_store_id');
                            }
                        }
                        if (in_array('Return', $scheduleTypes)) {
                            // For returns, filter pickup_store_id
                            if (!empty($storeLocations)) {
                                $q->orWhereIn('pickup_store_id', $storeLocations);
                            } else {
                                $q->orWhereNull('pickup_store_id');
                            }
                        }
                    });
                } else {
                    // If no schedule type, apply store location to both delivery & pickup
                    $query->where(function ($q) use ($storeLocations) {
                        if (!empty($storeLocations)) {
                            $q->whereIn('delivery_store_id', $storeLocations)->orWhereIn('pickup_store_id', $storeLocations);
                        } else {
                            $q->whereNull('delivery_store_id')->orWhereNull('pickup_store_id');
                        }
                    });
                }
            }

            if ($request->filled('rescheduled_only')) {
                $query->where(function ($q) {
                    $q->where('delivery_status', 'Reschedule')->orWhere('pickup_status', 'Reschedule');
                });
            }

            $perPage = $request->input('per_page', 30);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;
            $orderProducts = $query->orderBy($orderByField, 'asc')->paginate($perPageVal)->withQueryString(); // keeps filters in pagination links

            if ($request->filled('unassigned_equipment')) {
                $html = view('admin.order_management.schedule_assignment.partials._schedule_table', [
                    'orderProducts' => $orderProducts,
                ])->render();
            }else{
                $html = view('admin.order_management.schedules.partials._table', [
                    'orderProducts' => $orderProducts,
                ])->render();
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'total' => $orderProducts->count(),
            ]);
        }

        $categories = ProductCategory::getHierarchy();
        $stores = Store::orderBy('store_name')->get();

        $users = User::orderBy('first_name', 'asc')
            ->get()
            ->map(function ($user) {
                return [
                    'unique_id' => $user->unique_id,
                    'full_name' => $user->full_name,
                ];
            });
        $employees = $users->pluck('full_name', 'unique_id')->prepend('Select Employee', '');
        $rescheduleOrder = Order::whereHas('products', function ($q) {
            $q->where(function ($subQ) {
                $subQ->where('delivery_status', 'Reschedule')
                    ->orWhere('pickup_status', 'Reschedule');
            });
        })->count();
        // $all = $query->orderBy('delivery_date', 'asc')->limit(2)->get(); // keeps filters in pagination links

        // dd($all);

        return view('admin.order_management.schedules.index', ['categories' => $categories, 'stores' => $stores, 'employees' => $employees, 'rescheduleOrder' => $rescheduleOrder]);
    }
}
