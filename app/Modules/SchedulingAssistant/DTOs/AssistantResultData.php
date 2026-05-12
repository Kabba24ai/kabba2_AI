<?php

namespace App\Modules\SchedulingAssistant\DTOs;

class AssistantResultData
{
    public function __construct(
        public int $orderProductId,
        public array $orderWindow,
        public ?array $currentAssignment,
        public array $issues,
        public array $recommendedCandidates,
    ) {
    }

    public function toArray(): array
    {
        return [
            'order_product_id' => $this->orderProductId,
            'order_window' => $this->orderWindow,
            'current_assignment' => $this->currentAssignment,
            'issues' => $this->issues,
            'recommended_candidates' => array_map(
                fn ($candidate) => method_exists($candidate, 'toArray') ? $candidate->toArray() : $candidate,
                $this->recommendedCandidates
            ),
        ];
    }
}
