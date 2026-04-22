<?php

namespace App\Modules\SchedulingAssistant\DTOs;

use App\Modules\SchedulingAssistant\Enums\EligibilityStatus;

class AssignmentCandidateData
{
    public function __construct(
        public int $equipmentId,
        public string $equipmentName,
        public ?string $equipmentCode,
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
            'eligibility_status' => $this->eligibilityStatus->value,
            'score' => $this->score,
            'reasons' => $this->reasons,
            'flags' => $this->flags,
            'is_current_assignment' => $this->isCurrentAssignment,
        ];
    }
}
