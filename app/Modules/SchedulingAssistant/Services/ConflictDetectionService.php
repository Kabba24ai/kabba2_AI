<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\Support\DateRangeHelper;
use Illuminate\Support\Collection;

class ConflictDetectionService
{
    public function hasConflict(
        int $equipmentId,
        OrderProduct $targetOrderProduct,
        ?int $excludeOrderProductId = null
    ): bool {
        return $this->getConflicts($equipmentId, $targetOrderProduct, $excludeOrderProductId)->isNotEmpty();
    }

    public function getConflicts(
        int $equipmentId,
        OrderProduct $targetOrderProduct,
        ?int $excludeOrderProductId = null
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

        // 1. Soft assignments (pending/suggested — created by auto-assign)
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

        // 2. Hard assignments (equipment_id set directly on order_products — confirmed/rented)
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
            $existingEnd   = DateRangeHelper::combine($op->pickup_date,   $op->pickup_time);
            if (!$existingStart || !$existingEnd) {
                return false;
            }
            return DateRangeHelper::overlaps($requestedStart, $requestedEnd, $existingStart, $existingEnd);
        });

        return $softConflicts->values()->concat($hardConflicts->values());
    }
}
