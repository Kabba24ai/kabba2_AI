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

        $totalConflicts = count($doubleBookings) + count($damagedBookings) + count($noDirectAssignmentGroups);

        $employees = User::active()
            ->orderBy('first_name')
            ->get()
            ->pluck('full_name', 'unique_id');

        return view('admin.order_management.schedule_conflicts.index', compact(
            'doubleBookings',
            'damagedBookings',
            'noDirectAssignmentGroups',
            'totalConflicts',
            'employees',
        ));
    }
}
