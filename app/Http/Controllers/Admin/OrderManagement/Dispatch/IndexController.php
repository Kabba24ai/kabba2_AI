<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Enums\Dispatch\DispatchDateRangeMode;
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
    public function driverCards(Request $request)
    {
        $mode        = DispatchDateRangeMode::fromRequest($request->input('range'));
        $driverCards = $this->buildDriverCards($mode);
        $html        = view('admin.order_management.dispatch.partials._driver_cards', compact('driverCards', 'mode'))->render();
        return response()->json(['success' => true, 'html' => $html]);
    }

    private function buildDriverCards(DispatchDateRangeMode $mode): \Illuminate\Support\Collection
    {
        $drivers   = User::active()->where('is_driver', true)->orderBy('first_name')->get();
        $driverIds = $drivers->pluck('id');
        $endDate   = $mode->endDate();

        $deliveryQuery = OrderProduct::with(['order.customer', 'order.shippingAddress', 'deliveryStore', 'equipment', 'softAssignment.equipment', 'deliveryLoad'])
            ->whereIn('delivery_by', $driverIds)
            ->where('delivery_status', 'Pending')
            ->where('delivery_transport_mode', 'Truck')
            ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
            ->orderByRaw('COALESCE(dispatch_delivery_date, delivery_date) ASC');

        if ($endDate !== null) {
            $deliveryQuery->whereDate(
                \DB::raw('COALESCE(dispatch_delivery_date, delivery_date)'), '<=', $endDate
            );
        }

        // Driver cards obey the same financial-activity rule as the list
        // (Schedule Financial-Closure Alignment, 2026-07-20).
        \App\Services\Orders\OrderFinancialActivity::excludeInactiveOrderProducts($deliveryQuery);

        $deliveryJobs = $deliveryQuery->get()->groupBy('delivery_by');

        $returnQuery = OrderProduct::with(['order.customer', 'order.shippingAddress', 'pickupStore', 'equipment', 'softAssignment.equipment', 'pickupLoad'])
            ->whereIn('pickup_by', $driverIds)
            ->where('pickup_status', 'Pending')
            ->where('pickup_transport_mode', 'Truck')
            ->whereNotNull('pickup_date')
            ->orderByRaw('pickup_priority IS NULL, pickup_priority ASC')
            ->orderByRaw('COALESCE(dispatch_return_date, pickup_date) ASC');

        if ($endDate !== null) {
            $returnQuery->whereDate(
                \DB::raw('COALESCE(dispatch_return_date, pickup_date)'), '<=', $endDate
            );
        }

        \App\Services\Orders\OrderFinancialActivity::excludeInactiveOrderProducts($returnQuery);

        $returnJobs = $returnQuery->get()->groupBy('pickup_by');

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

            // Tag consecutive runs of the same combined-load so Blade can wrap them
            // in one grouped "load" card. Separate view uses _load_*; combined view
            // uses _cmb_load_* (the same model instances appear in both).
            $this->tagLoadRuns($deliveries, fn($j) => $j->delivery_load_id, fn($j) => $j->deliveryLoad, '_load');
            $this->tagLoadRuns($returns,    fn($j) => $j->pickup_load_id,   fn($j) => $j->pickupLoad,   '_load');
            $this->tagLoadRuns(
                $combined,
                fn($j) => $j->getAttribute('_slot') === 'delivery' ? $j->delivery_load_id : $j->pickup_load_id,
                fn($j) => $j->getAttribute('_slot') === 'delivery' ? $j->deliveryLoad : $j->pickupLoad,
                '_cmb_load'
            );

            $driver->delivery_jobs = $deliveries;
            $driver->return_jobs   = $returns;
            $driver->combined_jobs = $combined;
            return $driver;
        })->values();
    }

    /**
     * Mark consecutive runs of the same load in an already-sorted job collection so
     * the view can wrap each run in one grouped card. Sets, per job:
     *   {$p}_open  (bool)  first member of a run — also gets {$p} (the DispatchLoad)
     *                      and {$p}_count (members in the run)
     *   {$p}_close (bool)  last member of a run
     * A run of length 1 is treated as NOT a load group (a lone member renders as a
     * normal card — a load needs ≥2 to be meaningful).
     */
    private function tagLoadRuns(\Illuminate\Support\Collection $jobs, callable $loadIdOf, callable $loadOf, string $p): void
    {
        $items = $jobs->values();
        $n = $items->count();

        for ($i = 0; $i < $n; $i++) {
            $job  = $items[$i];
            $lid  = $loadIdOf($job);
            $prev = $i > 0 ? $loadIdOf($items[$i - 1]) : null;
            $next = $i < $n - 1 ? $loadIdOf($items[$i + 1]) : null;

            $isOpen  = $lid && $lid !== $prev;
            $isClose = $lid && $lid !== $next;

            // Count the run length starting at this open.
            $count = 0;
            if ($isOpen) {
                for ($j = $i; $j < $n && $loadIdOf($items[$j]) === $lid; $j++) {
                    $count++;
                }
            }

            // A single-item "run" is not a group.
            $isGroup = $lid && !($isOpen && $isClose && $count < 2);

            $job->setAttribute($p . '_open', $isGroup && $isOpen);
            $job->setAttribute($p . '_close', $isGroup && $isClose);
            $job->setAttribute($p . '_in', (bool) $isGroup);
            if ($isGroup && $isOpen) {
                $job->setAttribute($p, $loadOf($job));
                $job->setAttribute($p . '_count', $count);
            }
        }
    }

    public function __invoke(Request $request)
    {
        if ($request->ajax()) {
            // Payment Architecture Finalization (Phase 4B): 'order.lastPayment'
            // eager-load removed — Tier 2 already moved this query's own
            // filtering off it, and none of the Blade partials this
            // controller renders (_driver_cards/_split_table/_table/index)
            // read lastPayment/lastPaidPayment.
            $query = OrderProduct::query()
                ->with('equipment', 'equipment.productcategory', 'equipment.store', 'order', 'order.customer', 'product.categories', 'order.shippingAddress', 'order.notes', 'deliveryEmployee', 'pickupEmployee', 'deliveryStore', 'pickupStore', 'softAssignment.equipment.store')
                ->where('product_data->product_type', 'Rental')
                ->whereHas('order')
                ->whereNotNull('delivery_date');

             $query->where('delivery_status', '!=', 'Reschedule')
                      ->where('pickup_status', '!=', 'Reschedule');

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

            // Payment Architecture Finalization (Tier 2): these previously
            // matched only order.lastPayment (the single highest-id
            // order_payments row) — a split-payment order could match/miss
            // these filters based purely on which row happened to be
            // entered last. Now matches when ANY of the order's payment
            // rows qualifies. The status filter additionally uses
            // Order::scopeWherePaymentStatusFilter() so "Pending"/"Failed"
            // mean "still unresolved," not "ever had a row with that
            // status" — see the scope's own docblock.
            if ($request->filled('payment_method') && $request->payment_method != 'All Methods') {
                $query->whereHas('order.payments', function ($q) use ($request) {
                    $q->where('payment_method', $request->payment_method);
                });
            }

            if ($request->filled('payment_status') && $request->payment_status != 'All Status') {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->wherePaymentStatusFilter($request->payment_status);
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
                // 'Close as Completed' is the same terminal state as
                // 'Completed' for dispatch purposes — a row closed either
                // way (manually or automatically after a full refund/void)
                // is no longer actionable work.
                $query->where(function ($q) {
                    $q->whereNotIn('delivery_status', ['Completed', 'Close as Completed'])
                      ->orWhereNotIn('pickup_status', ['Completed', 'Close as Completed']);
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

            // Unassigned-only — show rows where the relevant selected side still
            // has no driver/tech. Respects the Delivery/Return context.
            if ($request->boolean('unassigned_only')) {
                $query->where(function ($q) use ($isDeliveryOnly, $isReturnOnly) {
                    if ($isDeliveryOnly) {
                        $q->whereNull('delivery_by');
                    } elseif ($isReturnOnly) {
                        $q->whereNull('pickup_by');
                    } else {
                        $q->where(function ($sub) {
                            $sub->whereNull('delivery_by')
                                ->where('delivery_status', 'Pending')
                                ->where('delivery_transport_mode', 'Truck');
                        })->orWhere(function ($sub) {
                            $sub->whereNull('pickup_by')
                                ->where('pickup_status', 'Pending')
                                ->where('pickup_transport_mode', 'Truck');
                        });
                    }
                });
            }

            // Dispatch date-range filter ("Show: All / 3 Days / Today") — the
            // same control that scopes the Driver Workload cards, applied
            // here too so the table reflects the same planning window. See
            // App\Enums\Dispatch\DispatchDateRangeMode: this only ever asks
            // for an end date, reusing the exact "Pending + <= end date"
            // logic the existing Today date_filter option already used
            // below — a future range there needs no changes here.
            $dispatchRangeMode = DispatchDateRangeMode::fromRequest($request->input('range'));
            $dispatchRangeEnd  = $dispatchRangeMode->endDate();

            if ($dispatchRangeEnd !== null) {
                $useBothDatesForRange = $isBothSelected || empty($scheduleTypes);
                $rangeDateField       = $isReturnOnly ? 'pickup_date' : 'delivery_date';

                if ($useBothDatesForRange) {
                    $query->where(function ($q) use ($dispatchRangeEnd) {
                        $q->where(function ($sub) use ($dispatchRangeEnd) {
                            $sub->where('delivery_status', 'Pending')
                                ->whereDate('delivery_date', '<=', $dispatchRangeEnd);
                        })->orWhere(function ($sub) use ($dispatchRangeEnd) {
                            $sub->where('pickup_status', 'Pending')
                                ->whereNotNull('pickup_date')
                                ->whereDate('pickup_date', '<=', $dispatchRangeEnd);
                        });
                    });
                } else {
                    $query->whereDate($rangeDateField, '<=', $dispatchRangeEnd);
                }
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

            // Schedule Financial-Closure Alignment (2026-07-20): same
            // read-side safeguard as Schedule — legacy rows on voided-out /
            // fully-refunded orders are not dispatchable work. Applied once
            // here so BOTH the split and combined views inherit it.
            \App\Services\Orders\OrderFinancialActivity::excludeInactiveOrderProducts($query);

            $viewMode = $request->input('view_mode', 'combined');
            $perPage  = $request->input('per_page', 30);

            // ---- SPLIT VIEW: two independent sorted lists side by side ----
            if ($viewMode === 'split') {
                $deliveries = (clone $query)
                    ->where('delivery_transport_mode', 'Truck')
                    ->when($request->boolean('unassigned_only'), fn ($q) => $q->whereNull('delivery_by'))
                    ->orderByRaw('delivery_priority IS NULL, delivery_priority ASC')
                    ->orderBy('delivery_date', 'asc')
                    ->get();

                $returns = (clone $query)
                    ->where('pickup_transport_mode', 'Truck')
                    ->where('delivery_status', 'Completed')
                    ->when($request->boolean('unassigned_only'), fn ($q) => $q->whereNull('pickup_by'))
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

        $categories     = ProductCategory::getHierarchy();
        $stores         = Store::orderBy('store_name')->get();
        $storesForModal = Store::active()->orderByAdmin()->pluck('store_name', 'unique_id');

        $allUsers    = User::active()->orderBy('first_name', 'asc')->get();
        $driverUsers = $allUsers->where('is_driver', true);

        // Equipment assign modal: uses unique_id (different endpoint) — all active employees
        $employees = $allUsers
            ->map(fn($u) => ['unique_id' => $u->unique_id, 'full_name' => $u->full_name])
            ->pluck('full_name', 'unique_id')
            ->prepend('Select Employee', '');

        // Driver / Tech assign modal: uses numeric id — all active employees
        $driverEmployees = $allUsers->pluck('full_name', 'id');

        // Employee phone map (id → phone) — all active employees
        $driverPhones = $allUsers->mapWithKeys(fn($u) => [
            $u->id => $u->mobile_phone ?: $u->phone_number ?: '',
        ]);

        // --- Driver workload cards (top of page, default Today Only) ---
        $mode        = DispatchDateRangeMode::Today;
        $driverCards = $this->buildDriverCards($mode);

        // --- Latest AI draft (today or most recent) ---
        $latestDraft = DispatchAiDraft::with(['assignments.orderProduct.order', 'assignments.recommendedDriver'])
            ->latest()
            ->first();

        return view('admin.order_management.dispatch.index', [
            'categories'      => $categories,
            'stores'          => $stores,
            'storesForModal'  => $storesForModal,
            'employees'       => $employees,
            'driverEmployees' => $driverEmployees,
            'driverPhones'    => $driverPhones,
            'driverCards'     => $driverCards,
            'mode'            => $mode,
            'latestDraft'     => $latestDraft,
        ]);
    }
}
