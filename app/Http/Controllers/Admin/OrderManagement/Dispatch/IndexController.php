<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchAiDraft;
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
    public function driverCards()
    {
        $driverCards = $this->buildDriverCards();
        $html = view('admin.order_management.dispatch.partials._driver_cards', compact('driverCards'))->render();
        return response()->json(['success' => true, 'html' => $html]);
    }

    private function buildDriverCards(): \Illuminate\Support\Collection
    {
        $drivers   = User::active()->where('is_driver', true)->orderBy('first_name')->get();
        $driverIds = $drivers->pluck('id');

        $deliveryJobs = OrderProduct::with(['order.customer', 'order.shippingAddress', 'deliveryStore', 'equipment', 'softAssignment.equipment'])
            ->whereIn('delivery_by', $driverIds)
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->whereDate('delivery_date', '<=', today())
            ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
            ->orderBy('delivery_date')
            ->get()
            ->groupBy('delivery_by');

        $returnJobs = OrderProduct::with(['order.customer', 'order.shippingAddress', 'pickupStore', 'equipment', 'softAssignment.equipment'])
            ->whereIn('pickup_by', $driverIds)
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->whereNotNull('pickup_date')
            ->whereDate('pickup_date', '<=', today())
            ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
            ->orderBy('pickup_date')
            ->get()
            ->groupBy('pickup_by');

        return $drivers->map(function ($driver) use ($deliveryJobs, $returnJobs) {
            $deliveries = $deliveryJobs->get($driver->id, collect());
            $returns    = $returnJobs->get($driver->id, collect());

            // Tag each job with its slot type so Blade knows how to render it in combined view
            $deliveries->each(fn($j) => $j->setAttribute('_slot', 'delivery'));
            $returns->each(fn($j)    => $j->setAttribute('_slot', 'return'));

            // Combined: merge and sort by respective priority (nulls last), then by date
            $combined = $deliveries->concat($returns)
                ->sortBy(fn($job) => $job->getAttribute('_slot') === 'delivery'
                    ? ($job->delivery_priority ?? 9999)
                    : ($job->pickup_priority   ?? 9999))
                ->values();

            $driver->delivery_jobs = $deliveries;
            $driver->return_jobs   = $returns;
            $driver->combined_jobs = $combined;
            return $driver;
        })->values();
    }

    public function __invoke(Request $request)
    {
        if ($request->ajax()) {
            $query = OrderProduct::query()
                ->with('equipment', 'equipment.productcategory', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.lastPayment', 'order.notes', 'deliveryEmployee', 'pickupEmployee')
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
            $scheduleTypes = [];

            if ($request->filled('schedule_type')) {
                $scheduleTypes = array_filter((array) $request->input('schedule_type', []), fn($v) => $v !== '' && $v !== 'false');
            }

            $isDeliverySelected = in_array('Delivery', $scheduleTypes);
            $isReturnSelected   = in_array('Return',   $scheduleTypes);
            $isBothSelected     = $isDeliverySelected && $isReturnSelected;
            $isReturnOnly       = $isReturnSelected  && !$isDeliverySelected;
            $isDeliveryOnly     = $isDeliverySelected && !$isReturnSelected;

            // Hide completed rows — only show actionable items
            if (!empty($scheduleTypes)) {
                $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected, $isReturnOnly) {
                    if ($isDeliverySelected) {
                        $q->where('delivery_status', 'Pending');
                    }
                    if ($isReturnSelected) {
                        if ($isReturnOnly) {
                            $q->where('pickup_status', 'Pending')
                              ->where('delivery_status', 'Completed');
                        } else {
                            $q->orWhere('pickup_status', 'Pending');
                        }
                    }
                });
            }

            // Always restrict to Truck transport mode
            if (empty($scheduleTypes)) {
                $query->where(function ($q) {
                    $q->where('delivery_transport_mode', 'Truck')
                      ->orWhere('pickup_transport_mode', 'Truck');
                });
                $query->where(function ($q) {
                    $q->where('delivery_status', '!=', 'Completed')
                      ->orWhere('pickup_status', '!=', 'Completed');
                });
            } else {
                $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected) {
                    if ($isDeliverySelected) {
                        $q->where('delivery_transport_mode', 'Truck');
                    }
                    if ($isReturnSelected) {
                        $q->orWhere('pickup_transport_mode', 'Truck');
                    }
                });
            }

            // Store location filter
            if ($request->filled('store_location')) {
                $storeLocations = array_filter((array) $request->input('store_location', []), fn($v) => $v !== '' && $v !== 'false');

                if (!empty($storeLocations)) {
                    if (!empty($scheduleTypes)) {
                        $query->where(function ($q) use ($isDeliverySelected, $isReturnSelected, $storeLocations) {
                            if ($isDeliverySelected) {
                                $q->orWhereIn('delivery_store_id', $storeLocations);
                            }
                            if ($isReturnSelected) {
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

            // Driver filter — scope by slot(s) matching the schedule type selection
            if ($request->filled('driver_id') && $request->driver_id !== '') {
                $driverId = (int) $request->driver_id;
                $query->where(function ($q) use ($driverId, $isDeliveryOnly, $isReturnOnly) {
                    if ($isDeliveryOnly) {
                        $q->where('delivery_by', $driverId);
                    } elseif ($isReturnOnly) {
                        $q->where('pickup_by', $driverId);
                    } else {
                        $q->where('delivery_by', $driverId)->orWhere('pickup_by', $driverId);
                    }
                });
            }

            // Date filter — reference the correct date column(s) per schedule selection
            if ($request->filled('date_filter')) {
                $dateFilter  = $request->date_filter;
                $useBothDates = $isBothSelected || empty($scheduleTypes);
                $dateField    = $isReturnOnly ? 'pickup_date' : 'delivery_date';

                if ($useBothDates) {
                    if ($dateFilter === 'today') {
                        // Couple status + date: only show actionable pending items due today or overdue
                        $query->where(function ($q) {
                            $q->where(function ($sub) {
                                $sub->where('delivery_status', 'Pending')
                                    ->whereDate('delivery_date', '<=', today());
                            })->orWhere(function ($sub) {
                                $sub->where('pickup_status', 'Pending')
                                    ->whereNotNull('pickup_date')
                                    ->whereDate('pickup_date', '<=', today());
                            });
                        });
                    } elseif ($dateFilter === 'week') {
                        $query->where(function ($q) {
                            $q->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
                              ->orWhereBetween('pickup_date', [now()->startOfWeek(), now()->endOfWeek()]);
                        });
                    } elseif ($dateFilter === 'month') {
                        $query->where(function ($q) {
                            $q->whereMonth('delivery_date', now()->month)
                              ->orWhereMonth('pickup_date', now()->month);
                        });
                    }
                } else {
                    if ($dateFilter === 'today') {
                        $query->whereDate($dateField, '<=', today());
                    } elseif ($dateFilter === 'week') {
                        $query->whereBetween($dateField, [now()->startOfWeek(), now()->endOfWeek()]);
                    } elseif ($dateFilter === 'month') {
                        $query->whereMonth($dateField, now()->month);
                    }
                }
            }

            $viewMode = $request->input('view_mode', 'combined');
            $perPage  = $request->input('per_page', 30);

            // ---- SPLIT VIEW: two independent sorted lists side by side ----
            if ($viewMode === 'split') {
                $deliveries = (clone $query)
                    ->where('delivery_transport_mode', 'Truck')
                    ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                    ->orderBy('delivery_date', 'asc')
                    ->get();

                $returns = (clone $query)
                    ->where('pickup_transport_mode', 'Truck')
                    ->where('delivery_status', 'Completed')
                    ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
                    ->orderBy('pickup_date', 'asc')
                    ->get();

                $html = view('admin.order_management.dispatch.partials._split_table',
                    compact('deliveries', 'returns'))->render();

                return response()->json([
                    'success' => true,
                    'html'    => $html,
                    'total'   => $deliveries->count() + $returns->count(),
                ]);
            }

            // ---- COMBINED VIEW: single sorted list with priority column ----
            $perPageVal = $perPage === 'all' ? max(1, $query->count()) : (int) $perPage;

            // Primary sort: priority (nulls last), secondary: date oldest first
            if ($isBothSelected || empty($scheduleTypes)) {
                $orderProducts = $query
                    ->orderByRaw("LEAST(COALESCE(delivery_priority, 9999), COALESCE(pickup_priority, 9999)) ASC")
                    ->orderByRaw("LEAST(COALESCE(delivery_date, '9999-12-31'), COALESCE(pickup_date, '9999-12-31')) ASC")
                    ->paginate($perPageVal)->withQueryString();
            } elseif ($isReturnOnly) {
                $orderProducts = $query
                    ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
                    ->orderBy('pickup_date', 'asc')
                    ->paginate($perPageVal)->withQueryString();
            } else {
                $orderProducts = $query
                    ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                    ->orderBy('delivery_date', 'asc')
                    ->paginate($perPageVal)->withQueryString();
            }

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

        $allUsers    = User::active()->orderBy('first_name', 'asc')->get();
        $driverUsers = $allUsers->where('is_driver', true);

        // Equipment assign modal: uses unique_id (different endpoint) — all active employees
        $employees = $allUsers
            ->map(fn($u) => ['unique_id' => $u->unique_id, 'full_name' => $u->full_name])
            ->pluck('full_name', 'unique_id')
            ->prepend('Select Employee', '');

        // Driver assign modal: uses numeric id — designated drivers only
        $driverEmployees = $driverUsers->pluck('full_name', 'id');

        // Driver phone map (id → phone) — designated drivers only
        $driverPhones = $driverUsers->mapWithKeys(fn($u) => [
            $u->id => $u->mobile_phone ?: $u->phone_number ?: '',
        ]);

        // --- Driver workload cards (top of page) ---
        $driverCards = $this->buildDriverCards();

        // --- Latest AI draft (today or most recent) ---
        $latestDraft = DispatchAiDraft::with(['assignments.orderProduct.order', 'assignments.recommendedDriver'])
            ->latest()
            ->first();

        return view('admin.order_management.dispatch.index', [
            'categories'      => $categories,
            'stores'          => $stores,
            'employees'       => $employees,
            'driverEmployees' => $driverEmployees,
            'driverPhones'    => $driverPhones,
            'driverCards'     => $driverCards,
            'latestDraft'     => $latestDraft,
        ]);
    }
}
