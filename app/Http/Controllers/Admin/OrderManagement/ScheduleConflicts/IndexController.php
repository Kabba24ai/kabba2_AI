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

        // ── Double Bookings ───────────────────────────────────────────────────
        $doubleBookings = [];

        if (!$section || $section === 'double_bookings') {
            $orderProducts = OrderProduct::with([
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

            $grouped = $orderProducts->groupBy('equipment_id');

            foreach ($grouped as $equipmentId => $products) {
                if ($products->count() < 2) continue;

                $list = $products->values();
                for ($i = 0; $i < $list->count(); $i++) {
                    for ($j = $i + 1; $j < $list->count(); $j++) {
                        $a = $list[$i];
                        $b = $list[$j];

                        $aStart = Carbon::parse($a->delivery_date)->startOfDay();
                        $aEnd   = Carbon::parse($a->pickup_date)->endOfDay();
                        $bStart = Carbon::parse($b->delivery_date)->startOfDay();
                        $bEnd   = Carbon::parse($b->pickup_date)->endOfDay();

                        if ($aStart->lte($bEnd) && $bStart->lte($aEnd)) {
                            $overlapStart = $aStart->gt($bStart) ? $aStart : $bStart;
                            $overlapEnd   = $aEnd->lt($bEnd) ? $aEnd : $bEnd;

                            $doubleBookings[] = [
                                'equipment'     => $a->equipment,
                                'a'             => $a,
                                'b'             => $b,
                                'overlap_start' => $overlapStart,
                                'overlap_end'   => $overlapEnd,
                            ];
                        }
                    }
                }
            }

            usort($doubleBookings, fn ($x, $y) => $x['overlap_start']->timestamp <=> $y['overlap_start']->timestamp);
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
        }

        // ── Totals & shared data ─────────────────────────────────────────────
        $totalConflicts = count($doubleBookings)
            + count($damagedBookings)
            + count($noDirectAssignmentGroups)
            + count($overdueEquipmentConflicts);

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
            'damagedBookings',
            'noDirectAssignmentGroups',
            'overdueEquipmentConflicts',
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
