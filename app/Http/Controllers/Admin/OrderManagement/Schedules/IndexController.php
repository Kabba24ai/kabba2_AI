<?php

namespace App\Http\Controllers\Admin\OrderManagement\Schedules;

use App\Http\Controllers\Controller;
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
        // Fetch real product-wise order data
        $query = OrderProduct::query()
            ->with('order', 'order.customer', 'order.shippingAddress', 'order.lastPayment')
            ->where('product_data->product_type', 'Rental')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['Pending', 'In Progress']);
            })
            ->whereNotNull('delivery_date');

        if ($request->filled('customer_name')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->customer_name . '%');
            });
        }

        if ($request->filled('customer_company_name')) {
            $query->whereHas('order.customer', function ($q) use ($request) {
                $q->where('company_name', 'like', '%' . $request->customer_company_name . '%');
            });
        }

        if ($request->filled('customer_phone')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('customer_phone', 'like', '%' . $request->customer_phone . '%');
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('product.categories', function ($q) use ($request) {
                $q->where('product_categories.id', $request->category);
            });
        }

        if ($request->filled('payment_status')) {
            $query->whereHas('order.lastPayment', function ($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            });
        }

        if ($request->filled('date_filter')) {
            $dateFilter = $request->date_filter;
            if ($dateFilter === 'today') {
                $query->whereDate('delivery_date', today());
            } elseif ($dateFilter === 'week') {
                $query->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dateFilter === 'month') {
                $query->whereMonth('delivery_date', now()->month);
            }
        }

        if ($request->filled('schedule_type')) {
            $scheduleTypes = array_filter(
                (array) $request->input('schedule_type', []),
                function($v) { return $v !== '' && $v !== 'false'; }
            );
            if(!empty($scheduleTypes)) {
                $query->where(function ($q) use ($scheduleTypes) {
                    if (in_array('Delivery', $scheduleTypes)) {
                        $q->where('is_delivery', 1);
                    }
                    if (in_array('Return', $scheduleTypes)) {
                        $q->orWhere('is_pickup_return', 1);
                    }
                });
            }
        } else {
            // Default to showing all transport modes
            $query->where(function ($q) {
                $q->where('is_delivery', 1)->orWhere('is_pickup_return', 1);
            });
        }

        if ($request->filled('transport_mode')) {
            $transportModes = array_filter(
                (array) $request->input('transport_mode', []),
                function($v) { return $v !== '' && $v !== 'false'; }
            );
            if (!empty($transportModes)) {
                $query->where(function ($q) use ($transportModes) {
                    if (in_array('Truck', $transportModes) && in_array('Store', $transportModes)) {
                        // BOTH: Truck and Store checked
                        $q->whereIn('delivery_type', ['Truck', 'Store'])
                        ->orWhereIn('pickup_type', ['Truck', 'Store']);
                    } else  {
                        $q->whereIn('delivery_type', $transportModes)->whereIn('pickup_type', $transportModes);
                    }
                });
            }
        } else {
            // Default to showing all transport modes
            $query->where(function ($q) {
                $q->whereIn('delivery_type', ['Truck','Store'])->orWhereIn('pickup_type', ['Truck','Store']);
            });
        }

        if ($request->filled('store_location')) {
            $storeLocations = array_filter((array) $request->input('store_location', []), function ($v) {
                return $v !== '' && $v !== 'false';
            });
            if (!empty($storeLocations)) {
                $query->whereIn('store_id', $storeLocations);
            }else {
                $query->whereNull('store_id');
            }
        } else {
            // Default to showing all transport modes
            $query->where(function ($q) {
                $q->where('store_id', '!=', null);
            });
        }

        if ($request->filled('rescheduled_only')) {
            $query->where(function ($q) {
                $q->where('delivery_status', 'Reschedule')->orWhere('pickup_status', 'Reschedule');
            });
        }

        $orderProducts = $query->orderBy('delivery_date', 'asc')->paginate(10)->withQueryString(); // keeps filters in pagination links

        if ($request->ajax()) {
            $html = view('admin.order_management.schedules.partials._table', [
                'orderProducts' => $orderProducts,
            ])->render();
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        }

        $categories = ProductCategory::orderByAdmin()->get();
        $stores = Store::orderBy('store_name')->get();

        return view('admin.order_management.schedules.index', ['orderProducts' => $orderProducts, 'categories' => $categories, 'stores' => $stores]);
    }
}
