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

        $assignments = EquipmentSoftAssign::query()
            ->with('orderProduct')
            ->where('equipment_id', $equipmentId)
            ->when($excludeOrderProductId, function ($query) use ($excludeOrderProductId) {
                $query->where('order_product_id', '!=', $excludeOrderProductId);
            })
            ->get();

        return $assignments->filter(function ($assignment) use ($requestedStart, $requestedEnd) {
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

            return DateRangeHelper::overlaps(
                $requestedStart,
                $requestedEnd,
                $existingStart,
                $existingEnd
            );
        })->values();
    }
}
