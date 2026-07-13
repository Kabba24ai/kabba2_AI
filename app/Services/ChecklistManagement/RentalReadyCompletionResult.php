<?php

namespace App\Services\ChecklistManagement;

/**
 * Immutable result of RentalReadyCompletionCalculator::calculate() — see
 * docs/checklist-system-audit/PR-B2_CALCULATOR_DESIGN.md §3 for the design
 * rationale (a typed object instead of a bare array).
 */
class RentalReadyCompletionResult
{
    /**
     * @param  array{total_questions:int, required_questions:int, optional_questions:int, required_items_completed:int, items_requiring_maintenance:int, damaged_items:int}  $counts
     */
    public function __construct(
        public readonly array $counts,
        public readonly bool $hasDamaged,
        public readonly bool $hasMaintenance,
        public readonly bool $allRentalReady,
        public readonly string $status,
        public readonly bool $anyAnswerMissing,
    ) {
    }
}
