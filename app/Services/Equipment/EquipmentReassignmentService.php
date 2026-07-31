<?php

namespace App\Services\Equipment;

use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSubstitutionLog;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\Services\ConflictDetectionService;
use App\Services\QueueLine\QueueLineEligibility;
use Illuminate\Support\Facades\DB;
use App\Services\QueueLine\QueueLineOperationException;
use InvalidArgumentException;

/**
 * Canonical "Switch Equipment" operation — the yard swaps the reserved unit
 * for the one actually being staged, BEFORE delivery.
 *
 * This changes the SAME canonical soft assignment every surface already
 * reads live (Schedule, Dispatch, Customer Checklist resources, Rental
 * Ready context, Equipment Management priority, Queue Line, conflict
 * detection — assignment-audit 2026-07-19: all resolve softAssignment at
 * read time). Mechanism: the exact delete-then-create idiom used by the
 * existing canonical writers (Schedules\AssignEquipmentController,
 * AutoAssignDirectService). No Queue-Line-specific assignment state exists.
 *
 * Conflict philosophy (approved): a swap is NEVER blocked by scheduling
 * conflicts. The Schedule Conflicts page computes conflicts live from
 * current assignments, so any conflict a swap creates enters the existing
 * resolution workflow automatically; the existing ConflictDetectionService
 * is additionally run AFTER the swap purely to report — the technician is
 * informed, never asked to resolve.
 *
 * Audit: one OrderHistory row per swap (dual attribution — the self-
 * selected employee physically doing it AND the logged-in user), plus an
 * EquipmentSubstitutionLog row when the replacement maps to a DIFFERENT
 * product (reusing the existing substitution structure; the dormant scoring
 * engine is not activated — score fields stay null).
 *
 * Fuel (Phase 3B hook): because the swap is canonical, a future fuel
 * verification keyed to (order_product_id, equipment_id) automatically
 * invalidates on swap — the verified unit no longer matches the live
 * assignment. No fuel state exists yet by design.
 */
final class EquipmentReassignmentService
{
    public const SOURCE_WEB = 'web';

    public const SOURCE_MOBILE = 'mobile';

    /**
     * Canonical picklist of switch reasons — the standard, fast-to-select
     * answers offered in the UI (web now; mobile can consume the same list).
     * A reason is still REQUIRED for a non-direct swap (see ::switch); this
     * only speeds the common cases. "Other" (free text) is offered by the UI
     * in addition to these and is not part of the list itself.
     */
    public const STANDARD_REASONS = [
        'Reserved unit unavailable',
        'Original unit down for maintenance or damage',
        'Better-suited unit available',
        'Correcting a mis-assignment',
        'Customer request',
    ];

    /**
     * Swap the soft-assigned unit for $replacement.
     *
     * @param  User  $performedBy  the employee physically staging (self-selected on shared terminals)
     * @param  User  $actor        the authenticated user (system audit actor)
     * @return array{changed: bool, classification: string, previous: ?Equipment, replacement: Equipment, conflicts: \Illuminate\Support\Collection}
     *
     * @throws InvalidArgumentException on integrity violations (never on scheduling conflicts)
     */
    public static function switch(
        OrderProduct $orderProduct,
        Equipment $replacement,
        User $performedBy,
        User $actor,
        string $source = self::SOURCE_WEB,
        ?string $reason = null,
    ): array {
        // ── Integrity guards (mirroring the canonical soft-assign path) ──
        if (! $orderProduct->order) {
            throw new QueueLineOperationException('This order no longer exists.', 'QUEUE_ITEM_NOT_FOUND');
        }

        // Hard-assigned = delivery already completed; the unit in the field
        // cannot be switched from the staging board (canonical 409 guards).
        if (! empty($orderProduct->equipment_id) || $orderProduct->checklistQuestions()->exists()) {
            throw new QueueLineOperationException('This item has already been delivered — its equipment can no longer be switched here.', 'QUEUE_ITEM_DELIVERED');
        }

        if ($replacement->trashed()) {
            throw new QueueLineOperationException('That equipment record is no longer active.', 'QUEUE_EQUIPMENT_INACTIVE');
        }

        // Physically rented units are in the field — they cannot be staged.
        if ($replacement->current_status?->value === 'rented') {
            throw new QueueLineOperationException("{$replacement->equipment_name} is currently rented out and cannot be staged.", 'QUEUE_EQUIPMENT_RENTED');
        }

        $previous = $orderProduct->softAssignment?->equipment;

        $classification = self::classify($replacement, (int) $orderProduct->product_id);

        // A non-direct replacement needs a stated reason — the audit trail
        // must say WHY a different product is leaving the yard.
        if ($classification !== QueueLineEligibility::ASSIGNMENT_DIRECT && blank($reason)) {
            throw new QueueLineOperationException('Please give a short reason when staging equipment that is not a direct match for the ordered product.', 'QUEUE_REASON_REQUIRED');
        }

        // Idempotency: switching to the already-assigned unit is a silent
        // no-op — no duplicate rows, no duplicate audit.
        if ($previous && $previous->id === $replacement->id) {
            return [
                'changed' => false,
                'classification' => $classification,
                'previous' => $previous,
                'replacement' => $replacement,
                'conflicts' => collect(),
            ];
        }

        DB::transaction(function () use ($orderProduct, $replacement, $previous, $performedBy, $actor, $source, $reason, $classification) {
            // The canonical mechanism — identical to Schedules\AssignEquipmentController
            $orderProduct->softAssignment()->delete();
            $orderProduct->softAssignment()->create([
                'equipment_id' => $replacement->id,
                'order_id' => $orderProduct->order_id,
                'assigned_by' => $performedBy->id,
            ]);

            $orderProduct->order->history()->create([
                'user_id' => $actor->id,
                'customer_id' => $orderProduct->order->customer_id,
                'action_date' => now(),
                'action_by' => OrderHistoryActionBy::User,
                'action' => OrderHistoryAction::EquipmentReassigned,
                'description' => sprintf(
                    'Equipment switched from %s to %s for %s by %s (%s).',
                    $previous?->equipment_name ?? 'unassigned',
                    $replacement->equipment_name,
                    $orderProduct->product_name,
                    $performedBy->full_name,
                    $source,
                ),
                'extras' => json_encode([
                    'order_product_id' => $orderProduct->id,
                    'order_product_unique_id' => $orderProduct->unique_id,
                    'previous_equipment_id' => $previous?->id,
                    'previous_equipment_name' => $previous?->equipment_name,
                    'previous_product_id' => $previous?->assigned_product_id,
                    'replacement_equipment_id' => $replacement->id,
                    'replacement_equipment_name' => $replacement->equipment_name,
                    'replacement_product_id' => $replacement->assigned_product_id,
                    'ordered_product_id' => $orderProduct->product_id,
                    'classification' => $classification,
                    'performed_by_id' => $performedBy->id,
                    'performed_by_name' => $performedBy->full_name,
                    'authenticated_user_id' => $actor->id,
                    'source' => $source,
                    'reason' => $reason,
                ]),
            ]);

            // Alternate product leaving the yard → record it in the EXISTING
            // substitution structure (no second substitution system; the
            // dormant scoring engine stays dormant — score fields null).
            if ($classification === QueueLineEligibility::ASSIGNMENT_ALTERNATE) {
                EquipmentSubstitutionLog::create([
                    'order_product_id' => $orderProduct->id,
                    'original_equipment_id' => $previous?->id ?? $replacement->id,
                    'substitute_equipment_id' => $replacement->id,
                    // Manual yard decision — not AI-scored (the score columns
                    // are NOT NULL; zeros + the evaluation_context source
                    // mark this as a human substitution, not an evaluation)
                    'overall_score' => 0,
                    'spec_score' => 0,
                    'rule_score' => 0,
                    'compatibility_score' => 0,
                    'rule_violations' => [],
                    'rule_supports' => [],
                    'spec_comparison' => [],
                    'requires_manager_approval' => false,
                    'evaluation_context' => [
                        'source' => 'queue_line_switch',
                        'channel' => $source,
                        'reason' => $reason,
                        'ordered_product_id' => $orderProduct->product_id,
                        'replacement_product_id' => $replacement->assigned_product_id,
                        'performed_by_id' => $performedBy->id,
                        'authenticated_user_id' => $actor->id,
                    ],
                    'evaluated_by' => $actor->id,
                ]);
            }
        });

        // Existing conflict detection, run AFTER the swap — informational
        // only. Conflicts flow to the Schedule Conflicts page automatically
        // (it computes live); the technician is never blocked or asked to
        // resolve anything.
        $conflicts = app(ConflictDetectionService::class)->getConflicts(
            $replacement->id,
            $orderProduct,
            $orderProduct->id,
        );

        return [
            'changed' => true,
            'classification' => $classification,
            'previous' => $previous,
            'replacement' => $replacement,
            'conflicts' => $conflicts,
        ];
    }

    /** Same three-state rule the Queue Line board uses (single definition kept there). */
    private static function classify(Equipment $replacement, int $orderedProductId): string
    {
        if ($replacement->assigned_product_id === null) {
            return QueueLineEligibility::ASSIGNMENT_UNKNOWN;
        }

        return (int) $replacement->assigned_product_id === $orderedProductId
            ? QueueLineEligibility::ASSIGNMENT_DIRECT
            : QueueLineEligibility::ASSIGNMENT_ALTERNATE;
    }
}
