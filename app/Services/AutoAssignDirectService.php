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

        $candidates = Equipment::query()
            ->where('assigned_product_id', $productId)
            ->whereIn('current_status', [
                EquipmentCurrentStatus::Available->value,
                EquipmentCurrentStatus::Maintenance->value,
            ])
            ->orderByRaw("FIELD(current_status, 'available', 'maintenance')")
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            return [
                'status' => 'skipped',
                'reason' => 'No direct-assignment equipment is Available or Maint. Hold',
            ];
        }

        $eligible = null;
        foreach ($candidates as $equipment) {
            if (!$this->conflictDetectionService->hasConflict($equipment->id, $orderProduct)) {
                $eligible = $equipment;
                break;
            }
        }

        if (!$eligible) {
            return [
                'status' => 'skipped',
                'reason' => 'All direct-assignment equipment has a scheduling conflict',
            ];
        }

        $orderProduct->softAssignment()->delete();
        $orderProduct->softAssignment()->create([
            'equipment_id' => $eligible->id,
            'order_id'     => $orderProduct->order_id,
            'assigned_by'  => auth()->id(),
        ]);

        return [
            'status'         => 'assigned',
            'equipment_name' => $eligible->equipment_name,
            'equipment_id'   => $eligible->equipment_id,
        ];
    }
}
