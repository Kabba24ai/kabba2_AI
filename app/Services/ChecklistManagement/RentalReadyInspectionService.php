<?php

namespace App\Services\ChecklistManagement;

use App\Enums\ChecklistManagement\RentalReadyLifecycleStatus;
use App\Enums\ChecklistManagement\RentalReadyResult;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyChecklistQuestionLog;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\Equipment\EquipmentStatusService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Canonical, server-authoritative writer for Rental Ready inspections
 * (Phase 2A — Inspection Integrity Foundation). BOTH the web admin path and
 * the mobile API path go through here; neither trusts the browser/device for
 * the checklist snapshot, the completion result, or equipment.current_status.
 *
 * Guarantees:
 *  - Immutability: only a Draft row is ever reused. A finalized inspection
 *    (Completed/Voided/etc.) is frozen — a genuinely new inspection gets a NEW
 *    row (new unique_id). Rental Ready, Maintenance Hold, and Damaged all
 *    survive later reinspections.
 *  - Lifecycle vs result are separate. Completion is inferred server-side from
 *    the master template (all required questions answered → a definitive
 *    result); an incomplete submission stays a Draft and does NOT change
 *    equipment status (an in-progress inspection never erases a completed one).
 *  - Snapshot is built from the master tables (question text, answer-option
 *    text, classification, required flag, PLUS section name/id and section &
 *    question order) so historical inspections survive later checklist edits.
 *  - Everything (header, answers, snapshot, log, completion, equipment status)
 *    runs in ONE transaction.
 *  - Idempotent: a repeated submit carrying the same completion key returns the
 *    already-recorded inspection instead of creating a duplicate.
 */
class RentalReadyInspectionService
{
    public function __construct(private RentalReadyCompletionCalculator $calculator) {}

    /**
     * @param  array<int, array{question_unique_id?: string, question_id?: int|string, answer_unique_id?: string, answer_id?: int|string, note?: string}>  $answers
     *
     * @throws RentalReadyInspectionException
     */
    public function record(
        Equipment $equipment,
        User $performedBy,
        ?User $actor,
        string $source,
        array $answers,
        ?int $equipmentHours,
        ?string $generalNotes,
        ?string $completionIdempotencyKey = null,
        ?string $inspectionUuid = null,
    ): RentalReadyInspectionResult {
        // 1. Idempotent replay — a retry with the same completion key returns
        //    the existing inspection, never a duplicate.
        if (filled($completionIdempotencyKey)) {
            $existing = EquipmentRentalReadyTemplate::where('completion_idempotency_key', $completionIdempotencyKey)->first();
            if ($existing) {
                return RentalReadyInspectionResult::replay($existing);
            }
        }

        // 2. Shared eligibility guard (web + mobile identical).
        RentalReadyEligibility::assertCanInspect($equipment);

        // 3. Server-truth master questions (ordered, with section + answers).
        $master = $this->loadMasterQuestions($equipment);

        // 4. Canonical snapshot: overlay the submitted selections onto master truth.
        $snapshot = $this->buildSnapshot($master, $this->indexSubmittedAnswers($answers));

        // 5. Server-authoritative result + lifecycle + counts.
        [$lifecycle, $result] = $this->determineOutcome($snapshot);
        $counts = $this->calculator->calculate($this->toCalculatorInput($snapshot))->counts;

        $isCompleting = $lifecycle === RentalReadyLifecycleStatus::Completed;

        try {
            return DB::transaction(function () use (
                $equipment, $inspectionUuid, $performedBy, $actor, $source, $snapshot, $counts,
                $lifecycle, $result, $isCompleting, $equipmentHours,
                $generalNotes, $completionIdempotencyKey
            ) {
                // 6. Immutability: reuse a Draft (specified or latest), else a new
                //    row. Resolved INSIDE the transaction with a row lock so
                //    concurrent saves reuse the same draft deterministically
                //    rather than racing; a finalized row is never returned here.
                $template = $this->resolveTargetRow($equipment, $inspectionUuid);
                $actorId = $actor?->id ?? $performedBy->id;

            // ── Header ──
            $template->fill([
                'equipment_id' => $equipment->id,
                'employee_id' => $performedBy->id,
                'employee_name' => $performedBy->full_name,
                'order_id' => $equipment->current_order_id,
                'order_product_id' => $equipment->current_order_product_id,
                'inspection_date' => now()->format('Y-m-d'),
                'inspection_time' => now()->format('H:i'),
                'equipment_hours' => $equipmentHours,
                'general_notes' => $generalNotes,
                'lifecycle_status' => $lifecycle->value,
                'result' => $result?->value,
                // Legacy string kept in sync for backward-compatible reads.
                'status' => $result?->legacyStatus() ?? 'Draft',
                'is_complete' => $result === RentalReadyResult::RentalReady,
                'total_questions' => $counts['total_questions'],
                'required_questions' => $counts['required_questions'],
                'optional_questions' => $counts['optional_questions'],
                'required_items_completed' => $counts['required_items_completed'],
                'items_requiring_maintenance' => $counts['items_requiring_maintenance'],
                'damaged_items' => $counts['damaged_items'],
            ]);
            if ($template->exists) {
                $template->updated_by = $actorId;
            } else {
                $template->created_by = $actorId;
            }
            if ($isCompleting) {
                $template->completed_at = now();
                if (filled($completionIdempotencyKey)) {
                    $template->completion_idempotency_key = $completionIdempotencyKey;
                }
            }
            $template->save();

            // ── Per-question rows (upsert by template+question; snapshot json) ──
            foreach ($snapshot as $q) {
                EquipmentRentalReadyChecklistQuestion::updateOrCreate(
                    [
                        'equipment_rental_ready_template_id' => $template->id,
                        'rental_ready_checklist_questions_id' => $q['question_id'],
                    ],
                    [
                        'selected_answer_id' => $q['selected_answer']['id'] ?? null,
                        'rental_ready_qa_json' => json_encode($q),
                        'general_notes' => $q['note'] ?? null,
                    ]
                );
            }

            // ── Append-only submission log (full canonical snapshot) ──
            EquipmentRentalReadyChecklistQuestionLog::create([
                'equipment_rental_ready_template_id' => $template->id,
                'rental_ready_all_qa_json' => json_encode([
                    'lifecycle_status' => $lifecycle->value,
                    'result' => $result?->value,
                    'source' => $source,
                    'counts' => $counts,
                    'questions' => $snapshot,
                ]),
                'action_by' => $actorId,
                'action_user_name' => optional($actor)->full_name ?? $performedBy->full_name,
                'inspection_date' => $template->inspection_date,
                'equipment_hours' => $template->equipment_hours,
                'inspector_name' => $performedBy->full_name,
            ]);

            // ── equipment.current_status — ONLY on completion (a draft never
            //    erases the prior completed status). Server maps result → status.
            if ($isCompleting && $result !== null) {
                match ($result) {
                    RentalReadyResult::Damaged => EquipmentStatusService::markDamagedFromRentalReady($equipment, $performedBy->id),
                    RentalReadyResult::RentalReady => EquipmentStatusService::markAvailableFromRentalReady($equipment, $performedBy->id),
                    RentalReadyResult::MaintenanceHold => EquipmentStatusService::markMaintenanceFromRentalReady($equipment, $performedBy->id),
                };
                if ($equipmentHours !== null) {
                    $equipment->forceFill(['equipment_hours' => $equipmentHours])->saveQuietly();
                }
            }

                return new RentalReadyInspectionResult($template->refresh(), $lifecycle, $result, $counts, false);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Concurrent completion carrying the SAME idempotency key: the
            // loser's INSERT hit the unique constraint and rolled back. Return
            // the winner's inspection (replay) rather than surfacing a 500.
            if (filled($completionIdempotencyKey) && str_contains($e->getMessage(), 'errt_completion_idem_key_unique')) {
                $existing = EquipmentRentalReadyTemplate::where('completion_idempotency_key', $completionIdempotencyKey)->first();
                if ($existing) {
                    return RentalReadyInspectionResult::replay($existing);
                }
            }

            throw $e;
        }
    }

    /**
     * Ordered master questions with section + answers, server-truth.
     *
     * @return Collection<int, array{question: mixed, order: int, section_id: ?int, section_name: ?string}>
     */
    private function loadMasterQuestions(Equipment $equipment): Collection
    {
        $checklistMaster = $equipment->checklistMaster;
        if (! $checklistMaster || ! $checklistMaster->rental_ready_template_id) {
            throw RentalReadyInspectionException::noChecklist((int) $equipment->id);
        }

        $templateQuestions = $checklistMaster->rentalReadyTemplate
            ->templateQuestions()
            ->with(['question.answers', 'question.category'])
            ->orderBy('index_number')
            ->get();

        $ordered = $templateQuestions
            ->map(fn ($tq, $i) => [
                'question' => $tq->question,
                'order' => $tq->index_number ?? ($i + 1),
                'section_id' => $tq->question?->category_id,
                'section_name' => $tq->question?->category?->category_name,
            ])
            ->filter(fn ($row) => $row['question'] !== null)
            ->values();

        if ($ordered->isEmpty()) {
            throw RentalReadyInspectionException::noQuestions((int) $equipment->id);
        }

        return $ordered;
    }

    /** Index the submitted answers by both question unique_id and numeric id. */
    private function indexSubmittedAnswers(array $answers): array
    {
        $byKey = [];
        foreach ($answers as $a) {
            foreach ([$a['question_unique_id'] ?? null, $a['question_id'] ?? null] as $key) {
                if ($key !== null && $key !== '') {
                    $byKey[(string) $key] = $a;
                }
            }
        }

        return $byKey;
    }

    /**
     * Build the canonical per-question snapshot: master truth (text,
     * classification, required, SECTION name/id, section & question ORDER)
     * overlaid with the submitted selection + note.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSnapshot(Collection $master, array $submittedByKey): array
    {
        $snapshot = [];

        foreach ($master as $row) {
            $q = $row['question'];
            $submitted = $submittedByKey[(string) $q->unique_id]
                ?? $submittedByKey[(string) $q->id]
                ?? null;

            $selectedAnswerKey = $submitted['answer_unique_id'] ?? $submitted['answer_id'] ?? null;

            $answers = $q->answers->map(function ($ans) use ($selectedAnswerKey) {
                $isSelected = $selectedAnswerKey !== null
                    && ((string) $ans->unique_id === (string) $selectedAnswerKey
                        || (string) $ans->id === (string) $selectedAnswerKey);

                return [
                    'id' => $ans->id,
                    'unique_id' => $ans->unique_id,
                    'answer_name' => $ans->answer_name,
                    'type' => $ans->type,
                    'index_number' => $ans->index_number,
                    'is_selected' => $isSelected,
                ];
            })->values()->all();

            $selected = collect($answers)->firstWhere('is_selected', true);

            $snapshot[] = [
                'question_id' => $q->id,
                'unique_id' => $q->unique_id,
                'question_name' => $q->question_name,
                'section_id' => $row['section_id'],
                'section_name' => $row['section_name'],
                'question_order' => $row['order'],
                'required_question' => (bool) $q->required_question,
                'note' => isset($submitted['note']) ? (string) $submitted['note'] : '',
                'answers' => $answers,
                'selected_answer' => $selected ?: null,
            ];
        }

        return $snapshot;
    }

    /** Calculator input shape (required + selected type), from the snapshot. */
    private function toCalculatorInput(array $snapshot): array
    {
        return array_map(fn ($q) => [
            'required_question' => (bool) $q['required_question'],
            'selected_answer' => isset($q['selected_answer']['type'])
                ? ['type' => $q['selected_answer']['type']]
                : null,
        ], $snapshot);
    }

    /**
     * Server-authoritative outcome. A definitive result (Rental Ready /
     * Maintenance Hold / Damaged) completes the inspection; an incomplete
     * submission (a required question unanswered, no damage) stays a Draft.
     *
     * @return array{0: RentalReadyLifecycleStatus, 1: ?RentalReadyResult}
     */
    private function determineOutcome(array $snapshot): array
    {
        $all = collect($snapshot);
        $required = $all->filter(fn ($q) => (bool) $q['required_question']);

        $hasDamaged = $all->contains(fn ($q) => data_get($q, 'selected_answer.type') === 'Damaged');
        $allRequiredAnswered = $required->every(fn ($q) => data_get($q, 'selected_answer') !== null);
        $allRequiredRentalReady = $required->every(fn ($q) => data_get($q, 'selected_answer.type') === 'Rental Ready');

        if ($hasDamaged) {
            return [RentalReadyLifecycleStatus::Completed, RentalReadyResult::Damaged];
        }
        if ($allRequiredAnswered && $allRequiredRentalReady) {
            return [RentalReadyLifecycleStatus::Completed, RentalReadyResult::RentalReady];
        }
        if ($allRequiredAnswered) {
            return [RentalReadyLifecycleStatus::Completed, RentalReadyResult::MaintenanceHold];
        }

        return [RentalReadyLifecycleStatus::Draft, null];
    }

    /**
     * Resolve the row to write. NEVER a terminal (frozen) inspection:
     *  - explicit inspection UUID → that draft (error if finalized);
     *  - else the latest Draft for this equipment + order context;
     *  - else a brand-new row.
     */
    private function resolveTargetRow(Equipment $equipment, ?string $inspectionUuid): EquipmentRentalReadyTemplate
    {
        if (filled($inspectionUuid)) {
            $row = EquipmentRentalReadyTemplate::where('unique_id', $inspectionUuid)
                ->where('equipment_id', $equipment->id)
                ->lockForUpdate()
                ->first();
            if ($row) {
                // Stale/offline protection: a specified inspection that has since
                // been finalized (by another device/path) can no longer be edited.
                if ($row->isTerminal()) {
                    throw RentalReadyInspectionException::inspectionFinalized((string) $inspectionUuid);
                }

                return $row;
            }
        }

        $query = EquipmentRentalReadyTemplate::where('equipment_id', $equipment->id)
            ->where('lifecycle_status', RentalReadyLifecycleStatus::Draft->value)
            ->lockForUpdate();

        $equipment->current_order_id
            ? $query->where('order_id', $equipment->current_order_id)
            : $query->whereNull('order_id');
        $equipment->current_order_product_id
            ? $query->where('order_product_id', $equipment->current_order_product_id)
            : $query->whereNull('order_product_id');

        return $query->orderByDesc('id')->first() ?? new EquipmentRentalReadyTemplate();
    }
}
