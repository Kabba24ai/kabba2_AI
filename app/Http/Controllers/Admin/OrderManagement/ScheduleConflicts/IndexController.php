<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleConflicts;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\Orders\OrderProduct;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Inventory Location Conflicts look-ahead window (days). Assigned rentals
     * starting further out than this are not flagged in Phase 1.
     */
    private const INVENTORY_LOCATION_LOOKAHEAD_DAYS = 14;

    public function __invoke(Request $request)
    {
        // ── Filter inputs ─────────────────────────────────────────────────────
        $search   = trim((string) $request->input('search', ''));
        $category = $request->input('category', '');   // product_category_id on equipment
        $store    = $request->input('store', '');       // store_id on equipment
        $section  = $request->input('section', '');     // which conflict type to show

        // ── Dropdown data ─────────────────────────────────────────────────────
        $categories = ProductCategory::orderBy('title')->pluck('title', 'id');
        $stores     = Store::orderBy('store_name')->pluck('store_name', 'id');

        // Helper: apply category / store / search to an OrderProduct query that
        // has equipment via whereHas('equipment', ...).
        $applyEquipmentFilters = function ($query) use ($search, $category, $store) {
            $query->when($category, fn ($q) => $q->whereHas('equipment',
                fn ($eq) => $eq->where('product_category_id', $category)
            ));
            $query->when($store, fn ($q) => $q->whereHas('equipment',
                fn ($eq) => $eq->where('store_id', $store)
            ));
            $query->when($search, fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('equipment', fn ($eq) => $eq
                        ->where('equipment_name', 'like', "%{$search}%")
                        ->orWhere('equipment_id', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")));
            }));
        };

        // ── Double Bookings + Back-to-Back Alerts ────────────────────────────
        $doubleBookings    = [];
        $backToBackAlerts  = [];

        if (!$section || in_array($section, ['double_bookings', 'back_to_back'])) {
            // Hard-assigned: equipment_id set directly on the order product
            $hardOps = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'equipment.productCategory', 'equipment.store',
                'softAssignment.equipment.store',
            ])
            ->whereNotNull('equipment_id')
            ->whereNotNull('delivery_date')
            ->whereNotNull('pickup_date')
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->where(fn ($q) => $q->where('pickup_status', '!=', 'Completed')->orWhereNull('pickup_status'))
            ->whereHas('order')
            ->tap($applyEquipmentFilters)
            ->get();

            // Soft-assigned: equipment linked via EquipmentSoftAssign (auto-assign path)
            $softOpsQuery = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'softAssignment.equipment.productCategory', 'softAssignment.equipment.store',
            ])
            ->whereNull('equipment_id')
            ->whereNotNull('delivery_date')
            ->whereNotNull('pickup_date')
            ->whereHas('softAssignment')
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->where(fn ($q) => $q->where('pickup_status', '!=', 'Completed')->orWhereNull('pickup_status'))
            ->whereHas('order');

            if ($category) {
                $softOpsQuery->whereHas('softAssignment.equipment', fn ($q) => $q->where('product_category_id', $category));
            }
            if ($store) {
                $softOpsQuery->whereHas('softAssignment.equipment', fn ($q) => $q->where('store_id', $store));
            }
            if ($search) {
                $softOpsQuery->where(fn ($q) => $q
                    ->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('softAssignment.equipment', fn ($eq) => $eq
                        ->where('equipment_name', 'like', "%{$search}%")
                        ->orWhere('equipment_id', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")))
                );
            }

            $softOps = $softOpsQuery->get();

            // Group by the resolved equipment_id (hard: equipment_id, soft: softAssignment.equipment_id)
            $grouped = $hardOps->concat($softOps)
                ->filter(fn ($op) => ($op->equipment_id ?? $op->softAssignment?->equipment_id))
                ->groupBy(fn ($op) => $op->equipment_id ?? $op->softAssignment?->equipment_id);

            foreach ($grouped as $equipmentId => $products) {
                if ($products->count() < 2) continue;

                $list = $products->values();
                for ($i = 0; $i < $list->count(); $i++) {
                    for ($j = $i + 1; $j < $list->count(); $j++) {
                        $a = $list[$i];
                        $b = $list[$j];

                        // Use startOfDay for both boundaries so that a same-day
                        // return/delivery pair is still detected as a touching pair.
                        $aStart = Carbon::parse($a->delivery_date)->startOfDay();
                        $aEnd   = Carbon::parse($a->pickup_date)->startOfDay();
                        $bStart = Carbon::parse($b->delivery_date)->startOfDay();
                        $bEnd   = Carbon::parse($b->pickup_date)->startOfDay();

                        if ($aStart->lte($bEnd) && $bStart->lte($aEnd)) {
                            $overlapStart = $aStart->gt($bStart) ? $aStart : $bStart;
                            $overlapEnd   = $aEnd->lt($bEnd) ? $aEnd : $bEnd;

                            $entry = [
                                'equipment'     => $a->equipment ?? $a->softAssignment?->equipment,
                                'a'             => $a,
                                'b'             => $b,
                                'overlap_start' => $overlapStart,
                                'overlap_end'   => $overlapEnd,
                            ];

                            // Back-to-back: one's pickup date equals the other's delivery date,
                            // and the earlier rental's delivery is strictly before that shared day —
                            // meaning the two periods only touch at a single boundary rather than
                            // truly overlapping. These are operationally risky but not double bookings.
                            $isBackToBack = ($aEnd->isSameDay($bStart) && $aStart->lt($bStart))
                                         || ($bEnd->isSameDay($aStart) && $bStart->lt($aStart));

                            if ($isBackToBack) {
                                $backToBackAlerts[] = $entry;
                            } else {
                                $doubleBookings[] = $entry;
                            }
                        }
                    }
                }
            }

            usort($doubleBookings,   fn ($x, $y) => $x['overlap_start']->timestamp <=> $y['overlap_start']->timestamp);
            usort($backToBackAlerts, fn ($x, $y) => $x['overlap_start']->timestamp <=> $y['overlap_start']->timestamp);

            // When the user filters to a specific type, clear the other.
            if ($section === 'double_bookings') {
                $backToBackAlerts = [];
            } elseif ($section === 'back_to_back') {
                $doubleBookings = [];
            }
        }

        // ── Damaged Equipment ─────────────────────────────────────────────────
        $damagedBookings = [];

        if (!$section || $section === 'damaged') {
            // Hard-assigned (equipment_id set) orders on damaged equipment, excluding fully returned
            $hardDamagedOps = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'equipment.productCategory', 'equipment.store',
                'softAssignment.equipment.store',
            ])
            ->whereNotNull('equipment_id')
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->where(fn ($q) => $q->where('pickup_status', '!=', 'Completed')->orWhereNull('pickup_status'))
            ->whereHas('order')
            ->whereHas('equipment', fn ($q) => $q->where('current_status', EquipmentCurrentStatus::Damaged->value))
            ->tap($applyEquipmentFilters)
            ->get();

            // Soft-assigned (auto-assigned but not yet confirmed) orders on damaged equipment
            $softDamagedQuery = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'softAssignment.equipment.productCategory', 'softAssignment.equipment.store',
            ])
            ->whereNull('equipment_id')
            ->whereHas('order')
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->where(fn ($q) => $q->where('pickup_status', '!=', 'Completed')->orWhereNull('pickup_status'))
            ->whereHas('softAssignment.equipment', fn ($eq) => $eq->where('current_status', EquipmentCurrentStatus::Damaged->value));

            if ($category) {
                $softDamagedQuery->whereHas('softAssignment.equipment', fn ($q) => $q->where('product_category_id', $category));
            }
            if ($store) {
                $softDamagedQuery->whereHas('softAssignment.equipment', fn ($q) => $q->where('store_id', $store));
            }
            if ($search) {
                $softDamagedQuery->where(fn ($q) => $q
                    ->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('softAssignment.equipment', fn ($eq) => $eq
                        ->where('equipment_name', 'like', "%{$search}%")
                        ->orWhere('equipment_id', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")))
                );
            }

            $softDamagedOps = $softDamagedQuery->get();

            // Merge hard and soft into groups keyed by equipment_id
            $damagedMap = [];

            foreach ($hardDamagedOps->groupBy('equipment_id') as $equipmentId => $ops) {
                $damagedMap[$equipmentId] = [
                    'equipment' => $ops->first()->equipment,
                    'orders'    => $ops->values(),
                ];
            }

            foreach ($softDamagedOps->groupBy(fn ($op) => $op->softAssignment?->equipment_id) as $equipmentId => $ops) {
                if (!$equipmentId) continue;
                if (isset($damagedMap[$equipmentId])) {
                    $damagedMap[$equipmentId]['orders'] = $damagedMap[$equipmentId]['orders']
                        ->concat($ops->values())
                        ->unique('id')
                        ->values();
                } else {
                    $damagedMap[$equipmentId] = [
                        'equipment' => $ops->first()->softAssignment?->equipment,
                        'orders'    => $ops->values(),
                    ];
                }
            }

            $damagedBookings = array_values($damagedMap);

            // Oldest first: each group's orders by delivery date, then groups
            // by their earliest delivery date (null dates sort last).
            $damagedDate = fn ($op) => $op?->delivery_date ? Carbon::parse($op->delivery_date)->timestamp : PHP_INT_MAX;
            foreach ($damagedBookings as &$damagedGroup) {
                $damagedGroup['orders'] = $damagedGroup['orders']->sortBy($damagedDate)->values();
            }
            unset($damagedGroup);
            usort($damagedBookings, fn ($x, $y) => $damagedDate($x['orders']->first()) <=> $damagedDate($y['orders']->first()));
        }

        // ── Overdue Equipment ─────────────────────────────────────────────────
        $today          = Carbon::today();
        $threeDaysAhead = $today->copy()->addDays(3);
        $overdueEquipmentConflicts = [];

        if (!$section || $section === 'overdue') {
            $overdueHardOps = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'equipment.productCategory', 'equipment.store',
            ])
            ->whereNotNull('equipment_id')
            ->where('delivery_status', 'Completed')
            ->where('pickup_status', 'Pending')
            ->whereNotNull('pickup_date')
            ->whereDate('pickup_date', '<', $today)
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->whereHas('order')
            ->tap($applyEquipmentFilters)
            ->get();

            if ($overdueHardOps->isNotEmpty()) {
                $overdueEquipmentIds = $overdueHardOps->pluck('equipment_id')->unique()->values()->toArray();

                $upcomingHard = OrderProduct::with([
                    'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                    'product.categories', 'equipment.productCategory', 'equipment.store', 'softAssignment.equipment',
                ])
                ->whereIn('equipment_id', $overdueEquipmentIds)
                ->whereNotNull('delivery_date')
                ->whereDate('delivery_date', '>=', $today)
                ->whereDate('delivery_date', '<=', $threeDaysAhead)
                ->whereHas('order')
                ->whereNotIn('id', $overdueHardOps->pluck('id')->toArray())
                ->get();

                $upcomingSoft = OrderProduct::with([
                    'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                    'product.categories', 'softAssignment.equipment.store',
                ])
                ->whereHas('softAssignment', fn ($q) => $q->whereIn('equipment_id', $overdueEquipmentIds))
                ->whereNotNull('delivery_date')
                ->whereDate('delivery_date', '>=', $today)
                ->whereDate('delivery_date', '<=', $threeDaysAhead)
                ->whereHas('order')
                ->get();

                foreach ($overdueHardOps->groupBy('equipment_id') as $equipmentId => $overdueGroup) {
                    $upcomingForEquipment = $upcomingHard
                        ->where('equipment_id', $equipmentId)
                        ->concat(
                            $upcomingSoft->filter(fn ($op) => (int) $op->softAssignment?->equipment_id === (int) $equipmentId)
                        )
                        ->unique('id')
                        ->values();

                    if ($upcomingForEquipment->isNotEmpty()) {
                        $overdueEquipmentConflicts[] = [
                            'equipment'       => $overdueGroup->first()->equipment,
                            'overdue_orders'  => $overdueGroup,
                            'upcoming_orders' => $upcomingForEquipment,
                        ];
                    }
                }

                // Oldest (most overdue) first, by the earliest missed return date.
                usort($overdueEquipmentConflicts, fn ($x, $y) =>
                    Carbon::parse($x['overdue_orders']->min('pickup_date'))->timestamp
                    <=> Carbon::parse($y['overdue_orders']->min('pickup_date'))->timestamp);
            }
        }

        // ── No Direct Assignment ──────────────────────────────────────────────
        $noDirectAssignmentGroups = [];

        if (!$section || $section === 'no_direct_assignment') {
            $ndaQuery = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'softAssignment.equipment.store',
            ])
            ->where('product_data->product_type', 'Rental')
            ->whereHas('order')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '>=', $today)
            ->whereNull('equipment_id')
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->where(fn ($q) => $q->where('pickup_status', '!=', 'Completed')->orWhereNull('pickup_status'))
            ->whereNotNull('product_id')
            ->whereNotExists(fn ($q) => $q
                ->select(DB::raw(1))
                ->from('equipment')
                ->whereColumn('equipment.assigned_product_id', 'order_products.product_id')
                ->whereNull('equipment.deleted_at')
            );

            if ($search) {
                $ndaQuery->where(fn ($q) => $q
                    ->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")))
                );
            }

            if ($category) {
                $ndaQuery->whereHas('product.categories', fn ($q) => $q->where('product_categories.id', $category));
            }

            $noDirectAssignmentOps = $ndaQuery->get();

            foreach ($noDirectAssignmentOps->groupBy('product_id') as $ops) {
                $noDirectAssignmentGroups[] = [
                    'product_name' => $ops->first()->product_name,
                    'product'      => $ops->first()->product,
                    'orders'       => $ops,
                ];
            }

            // Oldest first: each group's orders by delivery date, then groups
            // by their earliest delivery date.
            $ndaDate = fn ($op) => $op?->delivery_date ? Carbon::parse($op->delivery_date)->timestamp : PHP_INT_MAX;
            foreach ($noDirectAssignmentGroups as &$ndaGroup) {
                $ndaGroup['orders'] = $ndaGroup['orders']->sortBy($ndaDate)->values();
            }
            unset($ndaGroup);
            usort($noDirectAssignmentGroups, fn ($x, $y) => $ndaDate($x['orders']->first()) <=> $ndaDate($y['orders']->first()));
        }

        // ── Inventory Location Conflicts ──────────────────────────────────────
        // Assigned rental lines starting within the look-ahead window whose
        // equipment is not expected to be at the required fulfillment store.
        $inventoryLocationConflicts = [];

        if (!$section || $section === 'inventory_location') {
            $windowEnd = $today->copy()->addDays(self::INVENTORY_LOCATION_LOOKAHEAD_DAYS);

            // Hard-assigned upcoming lines (equipment_id set directly)
            $locHardOps = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'equipment.productCategory', 'equipment.store',
                'deliveryStore', 'softAssignment.equipment.store',
            ])
            ->whereNotNull('equipment_id')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '>=', $today)
            ->whereDate('delivery_date', '<=', $windowEnd)
            ->where(fn ($q) => $q->where('delivery_status', '!=', 'Completed')->orWhereNull('delivery_status'))
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->whereNotNull('delivery_store_id')
            ->whereHas('order')
            ->tap($applyEquipmentFilters)
            ->get();

            // Soft-assigned upcoming lines (auto-assigned equipment)
            $locSoftQuery = OrderProduct::with([
                'order.customer', 'order.shippingAddress', 'order.lastPayment', 'order.notes',
                'product.categories', 'deliveryStore',
                'softAssignment.equipment.productCategory', 'softAssignment.equipment.store',
            ])
            ->whereNull('equipment_id')
            ->whereHas('softAssignment')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '>=', $today)
            ->whereDate('delivery_date', '<=', $windowEnd)
            ->where(fn ($q) => $q->where('delivery_status', '!=', 'Completed')->orWhereNull('delivery_status'))
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->whereNotNull('delivery_store_id')
            ->whereHas('order');

            if ($category) {
                $locSoftQuery->whereHas('softAssignment.equipment', fn ($q) => $q->where('product_category_id', $category));
            }
            if ($store) {
                $locSoftQuery->whereHas('softAssignment.equipment', fn ($q) => $q->where('store_id', $store));
            }
            if ($search) {
                $locSoftQuery->where(fn ($q) => $q
                    ->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('softAssignment.equipment', fn ($eq) => $eq
                        ->where('equipment_name', 'like', "%{$search}%")
                        ->orWhere('equipment_id', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($o) => $o
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")))
                );
            }

            $locOps = $locHardOps->concat($locSoftQuery->get())
                ->filter(fn ($op) => ($op->equipment ?? $op->softAssignment?->equipment) !== null);

            if ($locOps->isNotEmpty()) {
                $locEquipmentIds = $locOps
                    ->map(fn ($op) => (int) ($op->equipment_id ?? $op->softAssignment?->equipment_id))
                    ->unique()->values()->all();

                // One batched fetch of every rental line (hard or soft) on the
                // involved equipment, so the "previous rental" lookup below is
                // pure in-memory work — no per-line queries.
                $historyLines = OrderProduct::with(['pickupStore', 'softAssignment'])
                    ->where(function ($q) use ($locEquipmentIds) {
                        $q->whereIn('equipment_id', $locEquipmentIds)
                          ->orWhereHas('softAssignment', fn ($s) => $s->whereIn('equipment_id', $locEquipmentIds));
                    })
                    ->whereNotNull('delivery_date')
                    ->whereHas('order')
                    ->get()
                    ->groupBy(fn ($op) => (int) ($op->equipment_id ?? $op->softAssignment?->equipment_id));

                foreach ($locOps as $op) {
                    $equipment       = $op->equipment ?? $op->softAssignment?->equipment;
                    $equipmentIdKey  = (int) ($op->equipment_id ?? $op->softAssignment?->equipment_id);
                    $requiredStore   = $op->deliveryStore;
                    if (!$equipment || !$requiredStore) {
                        continue;
                    }

                    // Expected Start Location: the planned return store of the
                    // immediately previous rental (latest line on this equipment
                    // starting before this one); when there is no previous rental
                    // or its return destination is not a store (custom/customer
                    // return), fall back to the equipment's home store.
                    $previous = ($historyLines->get($equipmentIdKey) ?? collect())
                        ->filter(fn ($h) => $h->id !== $op->id
                            && Carbon::parse($h->delivery_date)->lt(Carbon::parse($op->delivery_date)))
                        ->sortByDesc(fn ($h) => Carbon::parse($h->delivery_date)->timestamp * 100000 + $h->id)
                        ->first();

                    $expectedStore = $previous?->pickupStore ?? $equipment->store;
                    if (!$expectedStore) {
                        continue; // cannot determine an expected location — skip rather than false-flag
                    }

                    if ((int) $expectedStore->id !== (int) $requiredStore->id) {
                        $inventoryLocationConflicts[] = [
                            'op'             => $op,
                            'equipment'      => $equipment,
                            'expected_store' => $expectedStore,
                            'required_store' => $requiredStore,
                            'previous'       => $previous,
                        ];
                    }
                }

                // Oldest first — soonest rental start lands in grid position one
                usort($inventoryLocationConflicts, fn ($x, $y) =>
                    Carbon::parse($x['op']->delivery_date)->timestamp <=> Carbon::parse($y['op']->delivery_date)->timestamp);
            }
        }

        // ── Totals & shared data ─────────────────────────────────────────────
        $totalConflicts = count($doubleBookings)
            + count($backToBackAlerts)
            + count($damagedBookings)
            + count($noDirectAssignmentGroups)
            + count($overdueEquipmentConflicts)
            + count($inventoryLocationConflicts);

        $employees = User::active()
            ->orderBy('first_name')
            ->get()
            ->pluck('full_name', 'unique_id');

        $callUsers = User::active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'phone', 'email']);

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);

        return view('admin.order_management.schedule_conflicts.index', compact(
            'doubleBookings',
            'backToBackAlerts',
            'damagedBookings',
            'noDirectAssignmentGroups',
            'overdueEquipmentConflicts',
            'inventoryLocationConflicts',
            'totalConflicts',
            'employees',
            'categories',
            'stores',
            'search',
            'category',
            'store',
            'section',
            'callUsers',
            'customers',
            'suppliers',
        ));
    }
}
