<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleConflicts;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // Fetch all active order_products that have a hard equipment assignment and a valid date range.
        // "Active" = order not soft-deleted, equipment not yet returned/completed.
        $orderProducts = OrderProduct::with([
            'order.customer',
            'order.shippingAddress',
            'order.lastPayment',
            'order.notes',
            'product.categories',
            'equipment.productCategory',
            'equipment.store',
            'softAssignment.equipment.store',
        ])
        ->whereNotNull('equipment_id')
        ->whereNotNull('delivery_date')
        ->whereNotNull('pickup_date')
        ->where(function ($q) {
            $q->where('is_returned', '!=', 1)
              ->orWhereNull('is_returned');
        })
        ->whereHas('order')
        ->get();

        // Group by equipment_id and find overlapping pairs.
        $doubleBookings = [];

        $grouped = $orderProducts->groupBy('equipment_id');

        foreach ($grouped as $equipmentId => $products) {
            if ($products->count() < 2) {
                continue;
            }

            $list = $products->values();

            for ($i = 0; $i < $list->count(); $i++) {
                for ($j = $i + 1; $j < $list->count(); $j++) {
                    $a = $list[$i];
                    $b = $list[$j];

                    $aStart = Carbon::parse($a->delivery_date)->startOfDay();
                    $aEnd   = Carbon::parse($a->pickup_date)->endOfDay();
                    $bStart = Carbon::parse($b->delivery_date)->startOfDay();
                    $bEnd   = Carbon::parse($b->pickup_date)->endOfDay();

                    // Overlap: A starts before B ends AND B starts before A ends.
                    if ($aStart->lte($bEnd) && $bStart->lte($aEnd)) {
                        // Overlap window
                        $overlapStart = $aStart->gt($bStart) ? $aStart : $bStart;
                        $overlapEnd   = $aEnd->lt($bEnd) ? $aEnd : $bEnd;

                        $doubleBookings[] = [
                            'equipment'    => $a->equipment,
                            'a'            => $a,
                            'b'            => $b,
                            'overlap_start' => $overlapStart,
                            'overlap_end'   => $overlapEnd,
                        ];
                    }
                }
            }
        }

        // Stable sort: conflicts involving the soonest overlap first.
        usort($doubleBookings, fn ($x, $y) => $x['overlap_start']->timestamp <=> $y['overlap_start']->timestamp);

        // Orders assigned to damaged equipment — cannot be fulfilled until equipment is cleared.
        $damagedOrderProducts = OrderProduct::with([
            'order.customer',
            'order.shippingAddress',
            'order.lastPayment',
            'order.notes',
            'product.categories',
            'equipment.productCategory',
            'equipment.store',
            'softAssignment.equipment.store',
        ])
        ->whereNotNull('equipment_id')
        ->where(function ($q) {
            $q->where('is_returned', '!=', 1)->orWhereNull('is_returned');
        })
        ->whereHas('order')
        ->whereHas('equipment', function ($q) {
            $q->where('current_status', EquipmentCurrentStatus::Damaged->value);
        })
        ->get();

        $damagedBookings = [];
        foreach ($damagedOrderProducts->groupBy('equipment_id') as $ops) {
            $damagedBookings[] = [
                'equipment' => $ops->first()->equipment,
                'orders'    => $ops,
            ];
        }

        // Orders whose product has no Direct Assignment equipment configured at all.
        // Rule 3: Overdue Equipment — orders that are past their pickup date (not returned)
        // AND have an upcoming assignment on the same equipment within 3 days.
        $today          = Carbon::today();
        $threeDaysAhead = $today->copy()->addDays(3);

        $overdueHardOps = OrderProduct::with([
            'order.customer',
            'order.shippingAddress',
            'order.lastPayment',
            'order.notes',
            'product.categories',
            'equipment.productCategory',
            'equipment.store',
        ])
        ->whereNotNull('equipment_id')
        ->where('delivery_status', 'Completed')
        ->where('pickup_status', 'Pending')
        ->whereNotNull('pickup_date')
        ->whereDate('pickup_date', '<', $today)
        ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
        ->whereHas('order')
        ->get();

        $overdueEquipmentConflicts = [];

        if ($overdueHardOps->isNotEmpty()) {
            $overdueEquipmentIds = $overdueHardOps->pluck('equipment_id')->unique()->values()->toArray();

            // Upcoming orders on the same equipment (hard assigned) within 3 days
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

            // Upcoming orders on the same equipment (soft assigned) within 3 days
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

        $noDirectAssignmentOps = OrderProduct::with([
            'order.customer',
            'order.shippingAddress',
            'order.lastPayment',
            'order.notes',
            'product.categories',
            'softAssignment.equipment.store',
        ])
        ->where('product_data->product_type', 'Rental')
        ->whereHas('order')
        ->whereNotNull('delivery_date')
        ->whereNull('equipment_id')
        ->where(function ($q) {
            $q->where('is_returned', '!=', 1)->orWhereNull('is_returned');
        })
        ->whereNotNull('product_id')
        ->whereNotExists(function ($q) {
            $q->select(\DB::raw(1))
                ->from('equipment')
                ->whereColumn('equipment.assigned_product_id', 'order_products.product_id')
                ->whereNull('equipment.deleted_at');
        })
        ->get();

        $noDirectAssignmentGroups = [];
        foreach ($noDirectAssignmentOps->groupBy('product_id') as $ops) {
            $noDirectAssignmentGroups[] = [
                'product_name' => $ops->first()->product_name,
                'product'      => $ops->first()->product,
                'orders'       => $ops,
            ];
        }

        $totalConflicts = count($doubleBookings)
            + count($damagedBookings)
            + count($noDirectAssignmentGroups)
            + count($overdueEquipmentConflicts);

        $employees = User::active()
            ->orderBy('first_name')
            ->get()
            ->pluck('full_name', 'unique_id');

        return view('admin.order_management.schedule_conflicts.index', compact(
            'doubleBookings',
            'damagedBookings',
            'noDirectAssignmentGroups',
            'overdueEquipmentConflicts',
            'totalConflicts',
            'employees',
        ));
    }
}
