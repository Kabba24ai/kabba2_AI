<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

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
     * Dispatch is a driver-focused view of the schedule — Truck deliveries/returns only.
     * In-Store transport mode, Rescheduled Pending, and Overdue filters are excluded.
     */
    public function __invoke(Request $request)
    {
        if ($request->ajax()) {
            $query = OrderProduct::query()
                ->with('equipment', 'equipment.productcategory', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.lastPayment', 'order.notes')
                ->where('product_data->product_type', 'Rental')
                ->whereHas('order')
                ->whereNotNull('delivery_date');

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

            // --- SCHEDULE TYPE + TRANSPORT MODE ---
            // Dispatch is always Truck-only. In-Store is never included.
            $scheduleTypes = [];
            $orderByField  = 'delivery_date';
            $orderBy       = 'asc';

            if ($request->filled('schedule_type')) {
                $scheduleTypes = array_filter((array) $request->input('schedule_type', []), fn($v) => $v !== '' && $v !== 'false');
                $isReturnOnly  = in_array('Return', $scheduleTypes) && !in_array('Delivery', $scheduleTypes);

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

            if (in_array('Return', $scheduleTypes) && !in_array('Delivery', $scheduleTypes)) {
                $orderByField = 'pickup_date';
            }

            // Always restrict to Truck transport mode
            if (empty($scheduleTypes)) {
                $query->where(function ($q) {
                    $q->where('delivery_transport_mode', 'Truck')
                      ->orWhere('pickup_transport_mode', 'Truck');
                });
            } else {
                $query->where(function ($q) use ($scheduleTypes) {
                    if (in_array('Delivery', $scheduleTypes)) {
                        $q->where('delivery_transport_mode', 'Truck');
                    }
                    if (in_array('Return', $scheduleTypes)) {
                        $q->orWhere('pickup_transport_mode', 'Truck');
                    }
                });
            }

            // Store location filter
            if ($request->filled('store_location')) {
                $storeLocations = array_filter((array) $request->input('store_location', []), fn($v) => $v !== '' && $v !== 'false');

                if (!empty($storeLocations)) {
                    if (!empty($scheduleTypes)) {
                        $query->where(function ($q) use ($scheduleTypes, $storeLocations) {
                            if (in_array('Delivery', $scheduleTypes)) {
                                $q->orWhereIn('delivery_store_id', $storeLocations);
                            }
                            if (in_array('Return', $scheduleTypes)) {
                                $q->orWhereIn('pickup_store_id', $storeLocations);
                            }
                        });
                    } else {
                        $query->where(function ($q) use ($storeLocations) {
                            $q->whereIn('delivery_store_id', $storeLocations)
                              ->orWhereIn('pickup_store_id', $storeLocations);
                        });
                    }
                }
            }

            // Date filter
            if ($request->filled('date_filter')) {
                $dateFilter = $request->date_filter;
                if ($dateFilter === 'today') {
                    $query->whereDate($orderByField, '<=', today());
                    $orderBy = 'desc';
                } elseif ($dateFilter === 'week') {
                    $query->whereBetween($orderByField, [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($dateFilter === 'month') {
                    $query->whereMonth($orderByField, now()->month);
                }
            }

            $perPage    = $request->input('per_page', 30);
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

            $orderProducts = $query->orderBy($orderByField, $orderBy)->paginate($perPageVal)->withQueryString();

            $html = view('admin.order_management.dispatch.partials._table', [
                'orderProducts' => $orderProducts,
            ])->render();

            return response()->json([
                'success' => true,
                'html'    => $html,
                'total'   => $orderProducts->count(),
            ]);
        }

        $categories = ProductCategory::getHierarchy();
        $stores     = Store::orderBy('store_name')->get();

        $users = User::active()->orderBy('first_name', 'asc')
            ->get()
            ->map(fn($user) => [
                'unique_id' => $user->unique_id,
                'full_name' => $user->full_name,
            ]);

        $employees = $users->pluck('full_name', 'unique_id')->prepend('Select Employee', '');

        return view('admin.order_management.dispatch.index', [
            'categories' => $categories,
            'stores'     => $stores,
            'employees'  => $employees,
        ]);
    }
}
