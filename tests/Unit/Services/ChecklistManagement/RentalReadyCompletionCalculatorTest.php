<?php

namespace Tests\Unit\Services\ChecklistManagement;

use App\Services\ChecklistManagement\RentalReadyCompletionCalculator;
use PHPUnit\Framework\TestCase;

/**
 * PR-B2 Stage 2 — focused unit tests for RentalReadyCompletionCalculator in
 * isolation from any controller or database. Plain PHPUnit\Framework\TestCase
 * (no Laravel bootstrap), matching this codebase's convention for stateless
 * service unit tests (see tests/Unit/Services/TaxCalculationServiceTest.php) —
 * proves the calculator genuinely has no framework/DB dependency.
 */
class RentalReadyCompletionCalculatorTest extends TestCase
{
    private function required(?string $answerType): array
    {
        return [
            'required_question' => true,
            'selected_answer'   => $answerType === null ? null : ['type' => $answerType],
        ];
    }

    private function optional(?string $answerType): array
    {
        return [
            'required_question' => false,
            'selected_answer'   => $answerType === null ? null : ['type' => $answerType],
        ];
    }

    public function test_all_required_and_optional_rental_ready_is_complete_and_ready(): void
    {
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->required('Rental Ready'),
            $this->optional('Rental Ready'),
        ]);

        $this->assertSame('Rental Ready', $result->status);
        $this->assertTrue($result->allRentalReady);
        $this->assertFalse($result->hasDamaged);
        $this->assertFalse($result->hasMaintenance);
        $this->assertFalse($result->anyAnswerMissing);
        $this->assertSame([
            'total_questions' => 2,
            'required_questions' => 1,
            'optional_questions' => 1,
            'required_items_completed' => 1,
            'items_requiring_maintenance' => 0,
            'damaged_items' => 0,
        ], $result->counts);
    }

    public function test_damaged_takes_precedence_over_ready(): void
    {
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->required('Damaged'),
            $this->optional('Rental Ready'),
        ]);

        $this->assertSame('Damaged', $result->status);
        $this->assertTrue($result->hasDamaged);
        // allRentalReady is independently false here (required answer isn't 'Rental Ready'),
        // but the precedence rule must resolve to 'Damaged' regardless of that flag's value.
        $this->assertFalse($result->allRentalReady);
        $this->assertSame(1, $result->counts['damaged_items']);
        $this->assertSame(0, $result->counts['required_items_completed']);
    }

    public function test_damaged_takes_precedence_even_when_all_required_are_also_rental_ready(): void
    {
        // Not reachable today (one selected answer per question), but pins down that
        // the precedence check is an unconditional if/elseif, not a mutually-exclusive
        // computed pair — guards against a future refactor accidentally inverting it.
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->required('Rental Ready'),
            $this->required('Damaged'),
        ]);

        $this->assertSame('Damaged', $result->status);
        $this->assertTrue($result->hasDamaged);
        $this->assertFalse($result->allRentalReady);
    }

    public function test_unanswered_optional_question_does_not_block_ready_status(): void
    {
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->required('Rental Ready'),
            $this->optional(null),
        ]);

        $this->assertSame('Rental Ready', $result->status);
        $this->assertTrue($result->allRentalReady);
        // Still flagged for PR-B3/D3 observability, even though it doesn't block completion.
        $this->assertTrue($result->anyAnswerMissing);
        $this->assertSame(2, $result->counts['total_questions']);
        $this->assertSame(1, $result->counts['optional_questions']);
    }

    public function test_unanswered_required_question_falls_back_to_draft(): void
    {
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->required(null),
            $this->optional('Rental Ready'),
        ]);

        $this->assertSame('Draft', $result->status);
        $this->assertFalse($result->allRentalReady);
        $this->assertFalse($result->hasDamaged);
        $this->assertTrue($result->anyAnswerMissing);
        $this->assertSame(0, $result->counts['required_items_completed']);
    }

    public function test_maintenance_hold_answer_is_counted_and_falls_back_to_draft(): void
    {
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->required('Maint. Hold'),
        ]);

        $this->assertSame('Draft', $result->status);
        $this->assertTrue($result->hasMaintenance);
        $this->assertFalse($result->hasDamaged);
        $this->assertFalse($result->allRentalReady);
        $this->assertSame(1, $result->counts['items_requiring_maintenance']);
    }

    public function test_empty_question_list_is_rental_ready_by_vacuous_truth(): void
    {
        // Documents actual current behavior (every() on an empty collection is true) —
        // not a designed rule, but the exact behavior the pre-extraction code already
        // had, and which this extraction must not silently change.
        $result = (new RentalReadyCompletionCalculator())->calculate([]);

        $this->assertSame('Rental Ready', $result->status);
        $this->assertTrue($result->allRentalReady);
        $this->assertFalse($result->anyAnswerMissing);
        $this->assertSame(0, $result->counts['total_questions']);
    }

    public function test_no_required_questions_with_all_optional_answered_is_rental_ready(): void
    {
        // allRentalReady filters to required_question===true first; if there are none,
        // every() over an empty collection is vacuously true regardless of optional answers.
        $result = (new RentalReadyCompletionCalculator())->calculate([
            $this->optional('Maint. Hold'),
        ]);

        $this->assertSame('Rental Ready', $result->status);
        $this->assertTrue($result->allRentalReady);
        $this->assertSame(0, $result->counts['required_questions']);
        $this->assertSame(1, $result->counts['items_requiring_maintenance']);
    }
}
