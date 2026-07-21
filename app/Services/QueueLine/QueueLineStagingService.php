<?php

namespace App\Services\QueueLine;

use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Models\Orders\QueueLineKeyConfirmation;
use App\Services\Equipment\EquipmentReassignmentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The Mark as Staged workflow (admin readiness modal, 2026-07-20) — ONE
 * atomic operation that records complete outbound readiness:
 *
 *   1. the canonical Fuel Full verification (QueueFuelVerificationService —
 *      the SAME record the mobile fuel endpoint writes; no admin-only flag),
 *   2. the canonical Key confirmation (append-only sibling ledger),
 *   3. the canonical staged latch (QueueLineService::stage).
 *
 * All-or-nothing: everything runs in one transaction — a failure in any
 * step records nothing. Dual attribution everywhere: performed_by is the
 * employee who physically staged (self-selected on shared devices, exactly
 * the fuel-modal convention), created_by is the authenticated
 * administrator entering it.
 *
 * "Fully staged" (the green thumbs-up / the board's Staged section) is
 * DERIVED, never stored as its own flag: staged latch set AND a current
 * episode-bound fuel verification AND a current episode-bound key
 * confirmation. Switching equipment starts a new assignment episode, so a
 * staged card automatically falls back to Pending — nothing to clean up.
 *
 * Return to Pending is an append-only correction: it clears the staged
 * latch and reverses the CURRENT fuel and key records (with reason), never
 * deleting history.
 */
final class QueueLineStagingService
{
    public const RETURN_REASON = 'Returned to Pending from the Queue Line staging dialog';

    /**
     * Assign (or reassign) equipment AND mark it staged as ONE atomic
     * business operation — the Mark as Staged modal's assignment-capable
     * submission (corrective mission 2026-07-20). Queue Line is a primary
     * place the physical unit is confirmed or changed, so the modal may
     * INVOKE assignment; the assignment itself is the canonical operation
     * every entry point shares (EquipmentReassignmentService — the same
     * delete-then-create soft assignment Schedule Assignment, Order Details,
     * auto-assign, and mobile write, and every surface reads live). No
     * Queue-Line-only assignment state exists.
     *
     * @param  ?int  $baselineAssignmentId  the soft-assign episode id the modal
     *                                      DISPLAYED (null = shown unassigned).
     *                                      A physical change is only accepted
     *                                      from a current screen: if the live
     *                                      episode differs, the submission is
     *                                      rejected — a stale modal can never
     *                                      overwrite newer assignment work.
     * @param  Equipment  $target           the unit the user confirmed —
     *                                      either the displayed current unit
     *                                      or a replacement they selected
     * @param  ?string  $reason             required by the canonical switch
     *                                      rule when the replacement is not a
     *                                      direct match for the ordered product
     * @return array{staged: bool, replayed: bool, changed: bool, conflicts: Collection}
     *
     * @throws QueueLineOperationException
     */
    public static function assignAndStage(
        OrderProduct $orderProduct,
        ?int $baselineAssignmentId,
        Equipment $target,
        User $performedBy,
        User $actor,
        bool $fuelFull,
        bool $keyWithMachine,
        ?string $reason = null,
        string $source = QueueLineFuelVerification::SOURCE_WEB,
    ): array {
        // Same server-side re-enforcement as markStaged — fail BEFORE any
        // assignment work so a rejected checklist never moves equipment.
        if (! $fuelFull) {
            throw new QueueLineOperationException('Fuel must be Full before the equipment can be marked as staged.', 'QUEUE_FUEL_NOT_FULL');
        }

        if (! $keyWithMachine) {
            throw new QueueLineOperationException('The key must be with the machine before it can be marked as staged.', 'QUEUE_KEY_MISSING');
        }

        return DB::transaction(function () use ($orderProduct, $baselineAssignmentId, $target, $performedBy, $actor, $source, $reason) {
            // Serialize competing submissions on the ITEM itself — the
            // unassigned case has no soft-assign row to lock, so two admins
            // assigning simultaneously must queue here, not double-insert.
            OrderProduct::whereKey($orderProduct->id)->lockForUpdate()->value('id');

            $live = $orderProduct->softAssignment()->first();
            $changed = false;
            $conflicts = collect();

            if ((int) ($live?->equipment_id) !== (int) $target->id) {
                // A physical change is being requested. Only a screen showing
                // the CURRENT assignment may make it — reject stale modals.
                if (($live?->id) !== $baselineAssignmentId) {
                    throw new QueueLineOperationException(
                        'The equipment assignment changed while this screen was open. Review the current assignment shown below and try again.',
                        'QUEUE_ASSIGNMENT_CHANGED',
                    );
                }

                // Pin the relation to the row read under the lock so the
                // canonical switch audits the true previous unit.
                $orderProduct->setRelation('softAssignment', $live);

                // The canonical assign/reassign operation — same guards,
                // audit trail, substitution logging, and report-only conflict
                // detection as every other assignment entry point. Throws
                // (rolling this transaction back) on rented/inactive units or
                // a missing reason for non-direct replacements.
                $result = EquipmentReassignmentService::switch(
                    orderProduct: $orderProduct,
                    replacement: $target,
                    performedBy: $performedBy,
                    actor: $actor,
                    source: EquipmentReassignmentService::SOURCE_WEB,
                    reason: $reason,
                );

                $changed = $result['changed'];
                $conflicts = $result['conflicts'];

                $orderProduct->unsetRelation('softAssignment');
            }
            // else: the live unit already IS the target (use-current, or a
            // repeated submission after a completed reassignment) — nothing
            // to overwrite, so no baseline check; staging idempotency below.

            // The existing all-or-nothing readiness core (fuel + key +
            // staged latch). Runs inside THIS transaction: if any readiness
            // step fails, the assignment change above rolls back with it.
            $staged = self::markStaged(
                orderProduct: $orderProduct,
                expected: $target,
                performedBy: $performedBy,
                actor: $actor,
                fuelFull: true,
                keyWithMachine: true,
                source: $source,
            );

            return [
                'staged' => true,
                'replayed' => $staged['replayed'],
                'changed' => $changed,
                'conflicts' => $conflicts,
            ];
        });
    }

    /**
     * @param  Equipment  $expected  the unit shown on the modal — a stale
     *                               screen is rejected by the fuel service,
     *                               never re-pointed
     * @param  bool  $fuelFull       must be true — the modal disables submit
     *                               otherwise; the service re-enforces it
     * @param  bool  $keyWithMachine must be true — same rule
     * @return array{staged: bool, replayed: bool}
     *
     * @throws QueueLineOperationException
     */
    public static function markStaged(
        OrderProduct $orderProduct,
        Equipment $expected,
        User $performedBy,
        User $actor,
        bool $fuelFull,
        bool $keyWithMachine,
        string $source = QueueLineFuelVerification::SOURCE_WEB,
        ?string $idempotencyToken = null,
    ): array {
        // Server-side re-enforcement of the modal's enablement rules —
        // staging is COMPLETE readiness, never a partial note.
        if (! $fuelFull) {
            throw new QueueLineOperationException('Fuel must be Full before the equipment can be marked as staged.', 'QUEUE_FUEL_NOT_FULL');
        }

        if (! $keyWithMachine) {
            throw new QueueLineOperationException('The key must be with the machine before it can be marked as staged.', 'QUEUE_KEY_MISSING');
        }

        return DB::transaction(function () use ($orderProduct, $expected, $performedBy, $actor, $source, $idempotencyToken) {
            // 1) Canonical fuel verification — carries ALL the guards
            //    (eligibility, not delivered/completed/suppressed, active
            //    employee, stale-assignment rejection, episode idempotency)
            //    and lockForUpdate serialization for concurrent submissions.
            //    A replayed idempotency token (mobile/scanner retry) returns
            //    the original event untouched.
            $fuel = QueueFuelVerificationService::verify(
                orderProduct: $orderProduct,
                expected: $expected,
                performedBy: $performedBy,
                actor: $actor,
                source: $source,
                idempotencyToken: $idempotencyToken,
            );

            $assignment = $orderProduct->softAssignment()->first();

            // 2) Canonical key confirmation — same episode, same idempotency
            $keyReplayed = false;
            $currentKey = self::currentKeyForAssignment($orderProduct->id, $assignment->id);

            if ($idempotencyToken !== null && ! $currentKey) {
                $currentKey = QueueLineKeyConfirmation::where('idempotency_token', $idempotencyToken)->first();
            }

            if ($currentKey) {
                $keyReplayed = true;
            } else {
                QueueLineKeyConfirmation::create([
                    'order_product_id' => $orderProduct->id,
                    'order_id' => $orderProduct->order_id,
                    'equipment_id' => $assignment->equipment_id,
                    'equipment_soft_assign_id' => $assignment->id,
                    'action' => QueueLineKeyConfirmation::ACTION_CONFIRMED,
                    'performed_by' => $performedBy->id,
                    'created_by' => $actor->id,
                    'source' => $source,
                    'idempotency_token' => $idempotencyToken,
                ]);
            }

            // 3) Canonical staged latch (null-latch: first stage wins).
            //    Attributed to the ADMIN entering it — the physical work
            //    attribution lives on the fuel/key rows as performed_by.
            QueueLineService::stage($orderProduct->fresh(['softAssignment.equipment', 'queueLineItem']), $actor);

            return [
                'staged' => true,
                'replayed' => $fuel['replayed'] && $keyReplayed,
            ];
        });
    }

    /**
     * Return a staged item to Pending — append-only, history preserved:
     * the staged latch clears and the CURRENT fuel + key records are
     * reversed with a recorded reason (the historical rows stay untouched).
     * Both must be re-confirmed before the item can be staged again —
     * staging is all-or-nothing in BOTH directions.
     *
     * @param  User|null  $performedBy  the employee reporting the return
     *                                  (mobile); defaults to the actor (web)
     */
    public static function returnToPending(
        OrderProduct $orderProduct,
        User $actor,
        ?User $performedBy = null,
        string $source = QueueLineFuelVerification::SOURCE_WEB,
    ): void {
        $performedBy ??= $actor;

        DB::transaction(function () use ($orderProduct, $actor, $performedBy, $source) {
            $fuel = QueueFuelVerificationService::currentVerification($orderProduct);
            if ($fuel) {
                QueueFuelVerificationService::reverse(
                    verification: $fuel,
                    performedBy: $performedBy,
                    actor: $actor,
                    reason: self::RETURN_REASON,
                    source: $source,
                );
            }

            $key = self::currentKey($orderProduct);
            if ($key) {
                QueueLineKeyConfirmation::create([
                    'order_product_id' => $key->order_product_id,
                    'order_id' => $key->order_id,
                    'equipment_id' => $key->equipment_id,
                    'equipment_soft_assign_id' => $key->equipment_soft_assign_id,
                    'action' => QueueLineKeyConfirmation::ACTION_REVERSED,
                    'reversed_confirmation_id' => $key->id,
                    'performed_by' => $performedBy->id,
                    'created_by' => $actor->id,
                    'source' => $source,
                    'reason' => self::RETURN_REASON,
                ]);
            }

            QueueLineService::unstage($orderProduct, $actor);
        });
    }

    /** The item's CURRENT key confirmation — episode-bound like fuel. */
    public static function currentKey(OrderProduct $orderProduct): ?QueueLineKeyConfirmation
    {
        $assignment = $orderProduct->softAssignment;

        if (! $assignment) {
            return null;
        }

        return self::currentKeyForAssignment($orderProduct->id, $assignment->id);
    }

    /**
     * Fully staged = the green thumbs-up: staged latch + current fuel +
     * current key, all on the live assignment episode.
     *
     * @param  QueueLineFuelVerification|null  $fuel  pre-resolved current verification
     * @param  QueueLineKeyConfirmation|null  $key   pre-resolved current confirmation
     */
    public static function isFullyStaged(OrderProduct $orderProduct, ?QueueLineFuelVerification $fuel, ?QueueLineKeyConfirmation $key): bool
    {
        return ($orderProduct->queueLineItem?->isStaged() ?? false)
            && $fuel !== null
            && $key !== null;
    }

    /** Current confirmations for a whole board in ONE query, keyed by episode. */
    public static function keyMap(Collection $rows): Collection
    {
        return QueueLineKeyConfirmation::query()
            ->whereIn('order_product_id', $rows->pluck('id')->all() ?: [0])
            ->where('action', QueueLineKeyConfirmation::ACTION_CONFIRMED)
            ->whereDoesntHave('reversal')
            ->with('performedBy:id,first_name,last_name')
            ->get()
            ->keyBy('equipment_soft_assign_id');
    }

    private static function currentKeyForAssignment(int $orderProductId, int $assignmentId): ?QueueLineKeyConfirmation
    {
        return QueueLineKeyConfirmation::where('order_product_id', $orderProductId)
            ->where('equipment_soft_assign_id', $assignmentId)
            ->where('action', QueueLineKeyConfirmation::ACTION_CONFIRMED)
            ->whereDoesntHave('reversal')
            ->orderByDesc('id')
            ->first();
    }
}
