<?php

namespace App\Services;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\Services\ConflictDetectionService;

class AutoAssignDirectService
{
    public function __construct(
        private ConflictDetectionService $conflictDetectionService
    ) {}

    public function assignSingle(OrderProduct $orderProduct): array
    {
        $productId = (int) ($orderProduct->product_id ?? 0);

        if ($productId <= 0) {
            return [
                'status' => 'skipped',
                'reason' => 'No product linked to this order line',
            ];
        }

        // Check whether any direct-assignment equipment exists for this product at all.
        $hasDirectAssignment = Equipment::where('assigned_product_id', $productId)->exists();

        if (!$hasDirectAssignment) {
            return [
                'status' => 'no_direct_assignment',
                'reason' => 'No equipment has a Direct Assignment for this product',
            ];
        }

        // Priority 1 & 2: Available, then Maint. Hold — prefer conflict-free units first.
        $eligible = $this->findEligible($productId, $orderProduct, [
            EquipmentCurrentStatus::Available->value,
            EquipmentCurrentStatus::Maintenance->value,
        ], "FIELD(current_status, 'available', 'maintenance')");

        if ($eligible) {
            return $this->doAssign($orderProduct, $eligible, 'available_or_maintenance');
        }

        // Priority 3: Rented — only valid if dates don't overlap (new delivery after existing return).
        // ConflictDetectionService now checks hard assignments, so a Rented unit with a
        // date overlap will be correctly blocked here.
        $eligible = $this->findEligible($productId, $orderProduct, [
            EquipmentCurrentStatus::Rented->value,
        ], null);

        if ($eligible) {
            return $this->doAssign($orderProduct, $eligible, 'rented_no_overlap');
        }

        // Priority 4: Damaged — last resort.
        $eligible = $this->findEligible($productId, $orderProduct, [
            EquipmentCurrentStatus::Damaged->value,
        ], null);

        if ($eligible) {
            return $this->doAssign($orderProduct, $eligible, 'damaged_last_resort');
        }

        return [
            'status' => 'skipped',
            'reason' => 'All direct-assignment equipment has a scheduling conflict across all statuses',
        ];
    }

    private function findEligible(
        int $productId,
        OrderProduct $orderProduct,
        array $statuses,
        ?string $orderByRaw
    ): ?Equipment {
        $query = Equipment::query()
            ->where('assigned_product_id', $productId)
            ->whereIn('current_status', $statuses)
            ->orderBy('id');

        if ($orderByRaw) {
            $query->orderByRaw($orderByRaw);
        }

        foreach ($query->get() as $equipment) {
            if (!$this->conflictDetectionService->hasConflict($equipment->id, $orderProduct)) {
                return $equipment;
            }
        }

        return null;
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
