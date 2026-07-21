<?php

namespace App\Services\QueueLine;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * THE canonical owner of Queue Fuel Verification — all writes and all
 * current-state resolution. The Livewire board and the future mobile
 * endpoint call these same methods; no fuel rule may live anywhere else.
 *
 * Meaning (approved): an identified employee confirmed the CURRENTLY
 * ASSIGNED physical unit was full while preparing it for outbound release.
 * Binary Full-only; unverified = "Fuel Not Verified". Never a customer
 * purchase, a return reading, or a fuel-charge concept.
 *
 * CURRENT STATE (episode-bound): a verification is current only while its
 * equipment_soft_assign_id equals the item's LIVE soft-assignment row id
 * and no reversal row references it. Because Switch Equipment always
 * delete-then-creates a new soft-assign row, switching away — or away and
 * BACK — starts a new episode and the old sign-off never reactivates.
 * History is append-only; nothing transfers, migrates, or revives.
 */
final class QueueFuelVerificationService
{
    /**
     * Record a "Fuel Full" sign-off for the item's currently assigned unit.
     *
     * @param  Equipment  $expected     the unit the CALLER believes is assigned —
     *                                  a stale screen/scan is rejected, never re-pointed
     * @param  User  $performedBy       employee physically confirming (self-selected)
     * @param  User  $actor             authenticated application user
     * @return array{verification: QueueLineFuelVerification, replayed: bool}
     *
     * @throws InvalidArgumentException with an operator-readable message
     */
    public static function verify(
        OrderProduct $orderProduct,
        Equipment $expected,
        User $performedBy,
        User $actor,
        string $source = QueueLineFuelVerification::SOURCE_WEB,
        ?string $idempotencyToken = null,
    ): array {
        // Mobile/scanner retry replay — return the original event untouched
        if ($idempotencyToken !== null) {
            $existing = QueueLineFuelVerification::where('idempotency_token', $idempotencyToken)->first();
            if ($existing) {
                return ['verification' => $existing, 'replayed' => true];
            }
        }

        if (! $performedBy->exists || $performedBy->status !== 'Active') {
            throw new QueueLineOperationException('Select an active employee before verifying fuel.', 'QUEUE_EMPLOYEE_INVALID');
        }

        self::assertVerifiable($orderProduct);

        return DB::transaction(function () use ($orderProduct, $expected, $performedBy, $actor, $source, $idempotencyToken) {
            $assignment = self::lockCurrentAssignment($orderProduct, $expected);

            // Natural idempotency: one effective verification per episode.
            // A double-click or concurrent duplicate returns the same event.
            $current = self::currentForAssignment($orderProduct->id, $assignment->id);
            if ($current) {
                return ['verification' => $current, 'replayed' => true];
            }

            $verification = QueueLineFuelVerification::create([
                'order_product_id' => $orderProduct->id,
                'order_id' => $orderProduct->order_id,
                'equipment_id' => $assignment->equipment_id,
                'equipment_soft_assign_id' => $assignment->id,
                'action' => QueueLineFuelVerification::ACTION_VERIFIED,
                'performed_by' => $performedBy->id,
                'created_by' => $actor->id,
                'source' => $source,
                'idempotency_token' => $idempotencyToken,
            ]);

            return ['verification' => $verification, 'replayed' => false];
        });
    }

    /**
     * Reverse (void) a verification — append-only correction; the original
     * row is never touched. Repeating a reversal replays the existing one.
     *
     * @return array{reversal: QueueLineFuelVerification, replayed: bool}
     */
    public static function reverse(
        QueueLineFuelVerification $verification,
        User $performedBy,
        User $actor,
        string $reason,
        string $source = QueueLineFuelVerification::SOURCE_WEB,
        ?string $idempotencyToken = null,
    ): array {
        if ($verification->action !== QueueLineFuelVerification::ACTION_VERIFIED) {
            throw new QueueLineOperationException('Only a verification can be reversed.', 'QUEUE_INVALID_OPERATION');
        }

        if (blank($reason)) {
            throw new QueueLineOperationException('A short reason is required to reverse a fuel verification.', 'QUEUE_REASON_REQUIRED');
        }

        if (! $performedBy->exists || $performedBy->status !== 'Active') {
            throw new QueueLineOperationException('Select an active employee before reversing.', 'QUEUE_EMPLOYEE_INVALID');
        }

        if ($idempotencyToken !== null) {
            $existing = QueueLineFuelVerification::where('idempotency_token', $idempotencyToken)->first();
            if ($existing) {
                return ['reversal' => $existing, 'replayed' => true];
            }
        }

        return DB::transaction(function () use ($verification, $performedBy, $actor, $reason, $source, $idempotencyToken) {
            // One effective reversal per verification — replay, don't stack
            $already = QueueLineFuelVerification::where('reversed_verification_id', $verification->id)
                ->lockForUpdate()->first();
            if ($already) {
                return ['reversal' => $already, 'replayed' => true];
            }

            $reversal = QueueLineFuelVerification::create([
                'order_product_id' => $verification->order_product_id,
                'order_id' => $verification->order_id,
                'equipment_id' => $verification->equipment_id,
                'equipment_soft_assign_id' => $verification->equipment_soft_assign_id,
                'action' => QueueLineFuelVerification::ACTION_REVERSED,
                'reversed_verification_id' => $verification->id,
                'performed_by' => $performedBy->id,
                'created_by' => $actor->id,
                'source' => $source,
                'reason' => $reason,
                'idempotency_token' => $idempotencyToken,
            ]);

            return ['reversal' => $reversal, 'replayed' => false];
        });
    }

    /**
     * Lock and validate the item's live assignment against the unit the
     * caller's screen displayed — the shared guard set for EVERY staging
     * write (fuel, key, or neither): serialize on the soft-assign row,
     * require an assigned active unit, reject a stale screen. Public so
     * QueueLineStagingService can stage equipment with no fuel check while
     * keeping the exact same protections (applicability mission 2026-07-21).
     *
     * @throws QueueLineOperationException
     */
    public static function lockCurrentAssignment(OrderProduct $orderProduct, Equipment $expected): \App\Models\MaintenanceManagement\EquipmentSoftAssign
    {
        // Serialize concurrent sign-offs on the same assignment episode
        $assignment = $orderProduct->softAssignment()->lockForUpdate()->first();

        if (! $assignment || ! $assignment->equipment) {
            throw new QueueLineOperationException('No equipment is assigned to this item — assign a machine before verifying fuel.', 'QUEUE_EQUIPMENT_REQUIRED');
        }

        if ($assignment->equipment->trashed()) {
            throw new QueueLineOperationException('The assigned equipment record is no longer active.', 'QUEUE_EQUIPMENT_INACTIVE');
        }

        // Stale-screen guard: the sign-off is for a PHYSICAL unit. If the
        // assignment changed after the screen loaded, reject clearly.
        if ((int) $assignment->equipment_id !== (int) $expected->id) {
            throw new QueueLineOperationException(sprintf(
                'The assignment changed — this item is now %s, not %s. Refresh and verify the machine actually staged.',
                $assignment->equipment->equipment_name,
                $expected->equipment_name,
            ), 'QUEUE_ASSIGNMENT_CHANGED');
        }

        return $assignment;
    }

    /**
     * The item's CURRENT verification — episode-bound: only a sign-off made
     * against the live soft-assignment row counts; anything else is history.
     */
    public static function currentVerification(OrderProduct $orderProduct): ?QueueLineFuelVerification
    {
        $assignment = $orderProduct->softAssignment;

        if (! $assignment) {
            return null; // no unit assigned → nothing can be verified
        }

        return self::currentForAssignment($orderProduct->id, $assignment->id);
    }

    /** Full append-only history for an item, newest first. */
    public static function history(OrderProduct $orderProduct): Collection
    {
        return QueueLineFuelVerification::where('order_product_id', $orderProduct->id)
            ->with(['performedBy:id,first_name,last_name', 'createdBy:id,first_name,last_name', 'equipment:id,equipment_name,equipment_id'])
            ->orderByDesc('id')
            ->get();
    }

    private static function currentForAssignment(int $orderProductId, int $assignmentId): ?QueueLineFuelVerification
    {
        return QueueLineFuelVerification::where('order_product_id', $orderProductId)
            ->where('equipment_soft_assign_id', $assignmentId)
            ->where('action', QueueLineFuelVerification::ACTION_VERIFIED)
            ->whereDoesntHave('reversal')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Verification preconditions beyond the assignment itself: the item must
     * be under active Queue Line management. Public because staging shares
     * these preconditions even when no fuel row will be written.
     */
    public static function assertVerifiable(OrderProduct $orderProduct): void
    {
        if (! $orderProduct->order) {
            throw new QueueLineOperationException('This order no longer exists.', 'QUEUE_ITEM_NOT_FOUND');
        }

        // Delivered / hard-assigned items are out of the staging workflow
        if (! empty($orderProduct->equipment_id) || $orderProduct->is_delivered) {
            throw new QueueLineOperationException('This item has already been delivered — outbound fuel verification no longer applies.', 'QUEUE_ITEM_DELIVERED');
        }

        // Must be inside the canonical Queue Line eligibility window
        $eligible = QueueLineEligibility::eligibleQuery()
            ->whereKey($orderProduct->id)
            ->exists();

        if (! $eligible) {
            throw new QueueLineOperationException('This item is not currently on the Queue Line, so fuel cannot be verified here.', 'QUEUE_ITEM_NOT_ELIGIBLE');
        }

        $queueItem = $orderProduct->queueLineItem;

        if ($queueItem?->suppressed_forever) {
            throw new QueueLineOperationException('This item was removed from Queue Line management (Remove Forever) — restore it before verifying fuel.', 'QUEUE_ITEM_SUPPRESSED');
        }

        if ($queueItem?->completed_at !== null) {
            throw new QueueLineOperationException('This item has already left the Queue Line.', 'QUEUE_ITEM_ALREADY_COMPLETED');
        }
    }
}
