<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\Support\DateRangeHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConflictDetectionService
{
    /**
     * @param bool $ignoreSoftConflicts  Rule 2: soft assignments are tentative and must not
     *                                   block new soft assignments. Pass true when called from
     *                                   auto-assign logic so only hard (confirmed/rented) orders
     *                                   create a blocking conflict.
     */
    public function hasConflict(
        int $equipmentId,
        OrderProduct $targetOrderProduct,
        ?int $excludeOrderProductId = null,
        bool $ignoreSoftConflicts = false
    ): bool {
        return $this->getConflicts($equipmentId, $targetOrderProduct, $excludeOrderProductId, $ignoreSoftConflicts)->isNotEmpty();
    }

    public function getConflicts(
        int $equipmentId,
        OrderProduct $targetOrderProduct,
        ?int $excludeOrderProductId = null,
        bool $ignoreSoftConflicts = false
    ): Collection {
        $requestedStart = DateRangeHelper::combine(
            $targetOrderProduct->delivery_date,
            $targetOrderProduct->delivery_time
        );

        $requestedEnd = DateRangeHelper::combine(
            $targetOrderProduct->pickup_date,
            $targetOrderProduct->pickup_time
        );

        if (!$requestedStart || !$requestedEnd) {
            return collect();
        }

        $softConflicts = collect();

        // Rule 2: soft assignments are tentative — skip them when called from auto-assign
        // so an existing soft assignment never blocks a new soft assignment.
        if (!$ignoreSoftConflicts) {
            $softAssignments = EquipmentSoftAssign::query()
                ->with('orderProduct')
                ->where('equipment_id', $equipmentId)
                ->when($excludeOrderProductId, fn ($q) => $q->where('order_product_id', '!=', $excludeOrderProductId))
                ->get();

            $softConflicts = $softAssignments->filter(function ($assignment) use ($requestedStart, $requestedEnd) {
                if (!$assignment->orderProduct) {
                    return false;
                }
                $existingStart = DateRangeHelper::combine(
                    $assignment->orderProduct->delivery_date,
                    $assignment->orderProduct->delivery_time
                );
                $existingEnd = DateRangeHelper::combine(
                    $assignment->orderProduct->pickup_date,
                    $assignment->orderProduct->pickup_time
                );
                if (!$existingStart || !$existingEnd) {
                    return false;
                }
                return DateRangeHelper::overlaps($requestedStart, $requestedEnd, $existingStart, $existingEnd);
            });
        }

        // Hard assignments (equipment_id confirmed on order_products — rented/dispatched).
        // Rule 1: Rented equipment cannot be assigned until AFTER the return date.
        // Extend the existing order's end to end-of-day when no return time is set so that
        // a new order starting on the same calendar day as the return is treated as a conflict.
        $hardAssignedOps = OrderProduct::query()
            ->where('equipment_id', $equipmentId)
            ->whereNotNull('delivery_date')
            ->whereNotNull('pickup_date')
            ->where(fn ($q) => $q->where('is_returned', '!=', 1)->orWhereNull('is_returned'))
            ->whereHas('order')
            ->when($excludeOrderProductId, fn ($q) => $q->where('id', '!=', $excludeOrderProductId))
            ->get();

        $hardConflicts = $hardAssignedOps->filter(function ($op) use ($requestedStart, $requestedEnd) {
            $existingStart = DateRangeHelper::combine($op->delivery_date, $op->delivery_time);

            // If a specific return time is recorded, use it; otherwise use start-of-day on the
            // pickup date. This allows a same-day delivery to proceed (back-to-back), which is
            // flagged as a Back-to-Back Alert on the Schedule Conflicts page rather than blocked.
            $existingEnd = $op->pickup_time
                ? DateRangeHelper::combine($op->pickup_date, $op->pickup_time)
                : Carbon::parse($op->pickup_date)->startOfDay();

            if (!$existingStart || !$existingEnd) {
                return false;
            }

            return DateRangeHelper::overlaps($requestedStart, $requestedEnd, $existingStart, $existingEnd);
        });

        return $softConflicts->values()->concat($hardConflicts->values());
    }
}
