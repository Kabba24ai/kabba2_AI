<?php

namespace App\Modules\SchedulingAssistant\DTOs;

use App\Modules\SchedulingAssistant\Enums\EligibilityStatus;

class AssignmentCandidateData
{
    public function __construct(
        public int $equipmentId,
        public string $equipmentName,
        public ?string $equipmentCode,
        public string $relationshipType,
        public ?int $assignedProductId,
        public bool $allowUpgrades,
        public bool $allowDowngrades,
        public bool $downgradeRequiresApproval,
        public array $criticalMatchingCriteria,
        public EligibilityStatus $eligibilityStatus,
        public int $score,
        public array $reasons = [],
        public array $flags = [],
        public bool $isCurrentAssignment = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'equipment_id' => $this->equipmentId,
            'equipment_name' => $this->equipmentName,
            'equipment_code' => $this->equipmentCode,
            'relationship_type' => $this->relationshipType,
            'assigned_product_id' => $this->assignedProductId,
            'allow_upgrades' => $this->allowUpgrades,
            'allow_downgrades' => $this->allowDowngrades,
            'downgrade_requires_approval' => $this->downgradeRequiresApproval,
            'critical_matching_criteria' => $this->criticalMatchingCriteria,
            'eligibility_status' => $this->eligibilityStatus->value,
            'score' => $this->score,
            'reasons' => $this->reasons,
            'flags' => $this->flags,
            'is_current_assignment' => $this->isCurrentAssignment,
        ];
    }
}
