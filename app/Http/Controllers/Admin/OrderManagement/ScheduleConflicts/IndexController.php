<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleConflicts;

use App\Http\Controllers\Controller;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // Fetch all active order_products that have a hard equipment assignment and a valid date range.
        // "Active" = order not soft-deleted, equipment not yet returned/completed.
        $orderProducts = OrderProduct::with([
            'order.customer',
            'equipment.productCategory',
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

        $totalConflicts = count($doubleBookings);

        return view('admin.order_management.schedule_conflicts.index', compact('doubleBookings', 'totalConflicts'));
    }
}
