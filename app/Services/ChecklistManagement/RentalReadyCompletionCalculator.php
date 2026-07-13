<?php

namespace App\Services\ChecklistManagement;

/**
 * RentalReadyCompletionCalculator — the single completion/status algorithm for
 * Rental Ready inspections (PR-B2, Phase 2 Track B).
 *
 * Extracted verbatim from the mobile Rental Ready path
 * (App\Http\Controllers\Api\Admin\V1\Orders\RentalReadyChecklists\SaveController,
 * formerly inline at lines 136-201) — the confirmed-correct, server-trusted source
 * implementation per docs/checklist-system-audit/PR-B2_READINESS_REVIEW.md. See
 * PR-B2_CALCULATOR_DESIGN.md for the full design rationale.
 *
 * Stateless and side-effect free by design: no DB access, no Log:: calls, no
 * auth()/request() dependencies. Callers own persistence, logging, and dispatching
 * to EquipmentStatusService — this class only computes.
 */
class RentalReadyCompletionCalculator
{
    /**
     * @param  array<int, array{required_question: bool, selected_answer: array{type: string}|null}>  $questions
     */
    public function calculate(array $questions): RentalReadyCompletionResult
    {
        $questions = collect($questions);

        $counts = [
            'total_questions' => $questions->count(),
            'required_questions' => $questions->whereStrict('required_question', true)->count(),
            'optional_questions' => $questions->whereStrict('required_question', false)->count(),
            'required_items_completed' => $questions
                    ->filter(fn ($q) => (bool) ($q['required_question'] ?? false))
                    ->filter(fn ($q) => !is_null(data_get($q, 'selected_answer')))
                    ->filter(fn ($q) => data_get($q, 'selected_answer.type') === 'Rental Ready')
                    ->count(),

            'items_requiring_maintenance' => $questions
                    ->filter(fn ($q) => data_get($q, 'selected_answer.type') === 'Maint. Hold')
                    ->count(),

            'damaged_items' => $questions
                    ->filter(fn ($q) => data_get($q, 'selected_answer.type') === 'Damaged')
                    ->count(),
        ];

        $anyAnswerMissing = $questions->contains(function ($q) {
            return empty($q['selected_answer']);
        });

        $hasDamaged = $questions
            ->contains(fn ($q) => data_get($q, 'selected_answer.type') === 'Damaged');

        $hasMaintenance = $questions
            ->contains(fn ($q) => data_get($q, 'selected_answer.type') === 'Maint. Hold');

        $allRentalReady = $questions
            ->filter(fn ($q) => (bool) ($q['required_question'] ?? false))
            ->every(fn ($q) => data_get($q, 'selected_answer.type') === 'Rental Ready');

        if ($hasDamaged) {
            $status = 'Damaged';
        } elseif ($allRentalReady) {
            $status = 'Rental Ready';
        } else {
            $status = 'Draft';
        }

        return new RentalReadyCompletionResult(
            counts: $counts,
            hasDamaged: $hasDamaged,
            hasMaintenance: $hasMaintenance,
            allRentalReady: $allRentalReady,
            status: $status,
            anyAnswerMissing: $anyAnswerMissing,
        );
    }
}
