<?php

namespace App\Services\ChecklistManagement;

use App\Enums\ChecklistManagement\RentalReadyLifecycleStatus;
use App\Enums\ChecklistManagement\RentalReadyResult;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;

/**
 * Outcome of a RentalReadyInspectionService::record() call.
 */
class RentalReadyInspectionResult
{
    public function __construct(
        public readonly EquipmentRentalReadyTemplate $template,
        public readonly RentalReadyLifecycleStatus $lifecycle,
        public readonly ?RentalReadyResult $result,
        public readonly array $counts,
        public readonly bool $wasReplay,
    ) {}

    public function isCompleted(): bool
    {
        return $this->lifecycle === RentalReadyLifecycleStatus::Completed;
    }

    /** Rebuild a result object from a persisted row (idempotent replay). */
    public static function replay(EquipmentRentalReadyTemplate $template): self
    {
        return new self(
            template: $template,
            lifecycle: $template->lifecycle_status instanceof RentalReadyLifecycleStatus
                ? $template->lifecycle_status
                : RentalReadyLifecycleStatus::from((string) $template->lifecycle_status),
            result: $template->result instanceof RentalReadyResult
                ? $template->result
                : ($template->result ? RentalReadyResult::from((string) $template->result) : null),
            counts: [
                'total_questions' => (int) $template->total_questions,
                'required_questions' => (int) $template->required_questions,
                'optional_questions' => (int) $template->optional_questions,
                'required_items_completed' => (int) $template->required_items_completed,
                'items_requiring_maintenance' => (int) $template->items_requiring_maintenance,
                'damaged_items' => (int) $template->damaged_items,
            ],
            wasReplay: true,
        );
    }
}
