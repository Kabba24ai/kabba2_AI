<?php

namespace App\Services;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\Services\ConflictDetectionService;

class AutoAssignDirectService
{
    // Status tiers in priority order: best fit first.
    private const STATUS_TIERS = [
        [EquipmentCurrentStatus::Available->value, EquipmentCurrentStatus::Maintenance->value],
        [EquipmentCurrentStatus::Rented->value],
        [EquipmentCurrentStatus::Damaged->value],
    ];

    public function __construct(
        private ConflictDetectionService $conflictDetectionService
    ) {}

    /**
     * Assign the best available equipment to an order product.
     *
     * Rule: prefer conflict-free equipment within each status tier. If every unit
     * in the best tier has a conflict, still assign the first one in that tier —
     * the conflict will surface in the Schedule Conflicts module for review.
     * This means all three assignment paths (Observer, ⚡ per-row, Assign All)
     * share identical behaviour: always assign when direct-assignment equipment
     * exists, never silently skip due to a schedule overlap.
     */
    public function assignSingle(OrderProduct $orderProduct): array
    {
        $productId = (int) ($orderProduct->product_id ?? 0);

        if ($productId <= 0) {
            return [
                'status' => 'skipped',
                'reason' => 'No product linked to this order line',
            ];
        }

        if (!Equipment::where('assigned_product_id', $productId)->exists()) {
            return [
                'status' => 'no_direct_assignment',
                'reason' => 'No equipment has a Direct Assignment for this product',
            ];
        }

        $orderStoreId = $orderProduct->delivery_store_id;

        foreach (self::STATUS_TIERS as $statuses) {
            $candidates = Equipment::query()
                ->where('assigned_product_id', $productId)
                ->whereIn('current_status', $statuses)
                ->orderByRaw("FIELD(current_status, 'available', 'maintenance', 'rented', 'damaged')")
                ->orderBy('id')
                ->get();

            if ($candidates->isEmpty()) {
                continue;
            }

            // Pass 1: prefer a conflict-free unit at the same store as the order.
            if ($orderStoreId) {
                foreach ($candidates as $equipment) {
                    if (
                        $equipment->store_id == $orderStoreId &&
                        !$this->conflictDetectionService->hasConflict($equipment->id, $orderProduct, null, ignoreSoftConflicts: false)
                    ) {
                        return $this->doAssign($orderProduct, $equipment, 'conflict_free_store_match');
                    }
                }
            }

            // Pass 2: conflict-free unit at any store.
            foreach ($candidates as $equipment) {
                if (!$this->conflictDetectionService->hasConflict($equipment->id, $orderProduct, null, ignoreSoftConflicts: false)) {
                    return $this->doAssign($orderProduct, $equipment, 'conflict_free');
                }
            }

            // Pass 3: no conflict-free unit in this tier — assign the first one anyway.
            // The overlap will be visible in the Schedule Conflicts module.
            return $this->doAssign($orderProduct, $candidates->first(), 'conflict_flagged');
        }

        return [
            'status' => 'skipped',
            'reason' => 'No direct-assignment equipment found for this product in any status',
        ];
    }

    private function doAssign(OrderProduct $orderProduct, Equipment $equipment, string $priority): array
    {
        $orderProduct->softAssignment()->delete();
        $orderProduct->softAssignment()->create([
            'equipment_id' => $equipment->id,
            'order_id'     => $orderProduct->order_id,
            'assigned_by'  => auth()->id(),
        ]);

        return [
            'status'         => 'assigned',
            'priority'       => $priority,
            'equipment_name' => $equipment->equipment_name,
            'equipment_id'   => $equipment->equipment_id,
        ];
    }
}
