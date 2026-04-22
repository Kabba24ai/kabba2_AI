<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentServiceTask;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\DTOs\AssignmentCandidateData;
use App\Modules\SchedulingAssistant\Enums\EligibilityStatus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EquipmentEligibilityService
{
    protected ?array $serviceTaskColumnAvailability = null;

    public function __construct(
        protected ConflictDetectionService $conflictDetectionService
    ) {
    }

    public function evaluate(
        OrderProduct $orderProduct,
        Equipment $equipment,
        ?int $currentAssignedEquipmentId = null
    ): AssignmentCandidateData {
        $score = 100;
        $reasons = [];
        $flags = [];

        $isCurrentAssignment = $currentAssignedEquipmentId === (int) $equipment->id;

        $status = $this->normalizeStatus($equipment);

        $hasConflict = $this->conflictDetectionService->hasConflict(
            equipmentId: (int) $equipment->id,
            targetOrderProduct: $orderProduct,
            excludeOrderProductId: (int) $orderProduct->id
        );

        if ($hasConflict) {
            $score -= 100;
            $flags[] = 'overlap_conflict';
            $reasons[] = 'This unit is already assigned to an overlapping rental window.';
        } else {
            $reasons[] = 'No overlapping assignment found for the requested rental window.';
        }

        if ($status === 'damaged') {
            $score -= 60;
            $flags[] = 'damaged';
            $reasons[] = 'Unit is currently marked as damaged.';
        } elseif ($status === 'maintenance_hold' || $status === 'maint_hold') {
            $score -= 35;
            $flags[] = 'maintenance_hold';
            $reasons[] = 'Unit is currently on maintenance hold.';
        } elseif ($status === 'rented') {
            $score -= 25;
            $flags[] = 'status_rented';
            $reasons[] = 'Unit is currently marked as rented.';
        } else {
            $reasons[] = 'Unit status does not indicate a current hard readiness issue.';
        }

        $openTaskCount = $this->getOpenServiceTaskCount((int) $equipment->id);

        if ($openTaskCount > 0) {
            $score -= 15;
            $flags[] = 'open_service_tasks';
            $reasons[] = "Unit has {$openTaskCount} open service task(s).";
        }

        $orderStoreId = $orderProduct->delivery_store_id ?? null;
        $equipmentStoreId = $equipment->store_id ?? $equipment->location_id ?? null;

        if (!empty($orderStoreId) && !empty($equipmentStoreId) && (int) $orderStoreId !== (int) $equipmentStoreId) {
            $score -= 20;
            $flags[] = 'location_mismatch';
            $reasons[] = 'Unit appears to be assigned to a different store/location.';
        }

        $score = max(0, min(100, $score));
        $eligibility = $this->mapScoreToEligibility($score, $flags);

        return new AssignmentCandidateData(
            equipmentId: (int) $equipment->id,
            equipmentName: $this->resolveEquipmentName($equipment),
            equipmentCode: $this->resolveEquipmentCode($equipment),
            eligibilityStatus: $eligibility,
            score: $score,
            reasons: array_values(array_unique($reasons)),
            flags: array_values(array_unique($flags)),
            isCurrentAssignment: $isCurrentAssignment,
        );
    }

    protected function mapScoreToEligibility(int $score, array $flags): EligibilityStatus
    {
        if (in_array('overlap_conflict', $flags, true)) {
            return EligibilityStatus::BLOCKED;
        }

        if ($score >= 90) {
            return EligibilityStatus::READY;
        }

        if ($score >= 70) {
            return EligibilityStatus::CONDITIONAL;
        }

        if ($score >= 50) {
            return EligibilityStatus::APPROVAL_REQUIRED;
        }

        return EligibilityStatus::BLOCKED;
    }

    protected function normalizeStatus(Equipment $equipment): string
    {
        $raw = strtolower((string) (
            $equipment->status
            ?? $equipment->equipment_status
            ?? $equipment->availability_status
            ?? ''
        ));

        return match (true) {
            Str::contains($raw, 'damage') => 'damaged',
            Str::contains($raw, 'maint') => 'maintenance_hold',
            Str::contains($raw, 'rent') => 'rented',
            Str::contains($raw, 'avail') => 'available',
            default => $raw ?: 'unknown',
        };
    }

    protected function getOpenServiceTaskCount(int $equipmentId): int
    {
        $columnAvailability = $this->getServiceTaskColumnAvailability();

        $hasCompletedAt = $columnAvailability['completed_at'];
        $hasCheckedDate = $columnAvailability['checked_date'];
        $hasStatus = $columnAvailability['status'];

        // If the table has no completion signal, avoid penalizing the equipment.
        if (!$hasCompletedAt && !$hasCheckedDate && !$hasStatus) {
            return 0;
        }

        return EquipmentServiceTask::query()
            ->where('equipment_id', $equipmentId)
            ->where(function ($query) use ($hasCompletedAt, $hasCheckedDate, $hasStatus) {
                if ($hasCompletedAt) {
                    $query->whereNull('completed_at');
                } elseif ($hasCheckedDate) {
                    $query->whereNull('checked_date');
                }

                if ($hasStatus) {
                    $statusQuery = function ($subQuery) {
                        $subQuery->whereNotNull('status')
                            ->whereNotIn('status', ['completed', 'closed']);
                    };

                    if ($hasCompletedAt || $hasCheckedDate) {
                        $query->orWhere($statusQuery);
                    } else {
                        $query->where($statusQuery);
                    }
                }
            })
            ->count();
    }

    protected function getServiceTaskColumnAvailability(): array
    {
        if ($this->serviceTaskColumnAvailability !== null) {
            return $this->serviceTaskColumnAvailability;
        }

        $table = (new EquipmentServiceTask())->getTable();

        $this->serviceTaskColumnAvailability = [
            'completed_at' => Schema::hasColumn($table, 'completed_at'),
            'checked_date' => Schema::hasColumn($table, 'checked_date'),
            'status' => Schema::hasColumn($table, 'status'),
        ];

        return $this->serviceTaskColumnAvailability;
    }

    protected function resolveEquipmentName(Equipment $equipment): string
    {
        return (string) (
            $equipment->name
            ?? $equipment->title
            ?? $equipment->equipment_name
            ?? ('Equipment #' . $equipment->id)
        );
    }

    protected function resolveEquipmentCode(Equipment $equipment): ?string
    {
        return $equipment->equipment_id_code
            ?? $equipment->unit_number
            ?? $equipment->stock_number
            ?? $equipment->code
            ?? null;
    }
}
