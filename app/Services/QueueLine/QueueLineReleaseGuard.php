<?php

namespace App\Services\QueueLine;

use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\OrderProduct;

/**
 * THE single release-enforcement rule (Phase 3C): outbound equipment on a
 * Queue Line-managed item may not leave the yard without a CURRENT Queue
 * Fuel Verification for the exact unit being released.
 *
 * Scope — enforcement applies ONLY when the item is under active Queue
 * Line management (the canonical eligibility predicate: Rental, order
 * alive, delivery leg Pending, dated through tomorrow, Truck or Store):
 *  - returns / Return Only legs / retail lines: never touched (eligibility
 *    already excludes them; callers only invoke this on delivery-leg actions)
 *  - Remove Forever: outside Queue Line management by approved Phase 1
 *    policy (incl. the fuel requirement) → not enforced
 *  - Remove Today: HIDES the card only — fuel is still enforced
 *  - already-completed items: repeat driver actions replay freely
 *  - items outside the date window (early releases): not queue-managed →
 *    not enforced (documented scope boundary)
 *
 * Fuel state comes ONLY from QueueFuelVerificationService (episode-bound,
 * Phase 3B) — a historical verification on a prior unit or prior episode
 * never satisfies release.
 */
final class QueueLineReleaseGuard
{
    public const CODE_EQUIPMENT_REQUIRED = 'QUEUE_EQUIPMENT_REQUIRED';

    public const CODE_FUEL_REQUIRED = 'QUEUE_FUEL_VERIFICATION_REQUIRED';

    public const CODE_ASSIGNMENT_CHANGED = 'QUEUE_ASSIGNMENT_CHANGED';

    /**
     * @param  Equipment|null  $releasing  the unit the caller is physically
     *                                     releasing (e.g. the checklist's
     *                                     equipment) — null = the assigned unit
     * @return array|null null = release permitted; otherwise a structured
     *                    block: {code, message, order_product_unique_id,
     *                    equipment, fuel_state, corrective_action}
     */
    public static function check(OrderProduct $orderProduct, ?Equipment $releasing = null): ?array
    {
        if (! self::isQueueManaged($orderProduct)) {
            return null;
        }

        $assignment = $orderProduct->softAssignment;

        if (! $assignment?->equipment) {
            return self::blocked(
                self::CODE_EQUIPMENT_REQUIRED,
                'Assign a machine to this item on the Queue Line before releasing it.',
                $orderProduct,
                null,
            );
        }

        if ($releasing && (int) $releasing->id !== (int) $assignment->equipment_id) {
            return self::blocked(
                self::CODE_ASSIGNMENT_CHANGED,
                sprintf(
                    'This item is staged with %s, not %s. Switch the Queue Line assignment (or release the staged machine) first.',
                    $assignment->equipment->equipment_name,
                    $releasing->equipment_name,
                ),
                $orderProduct,
                $assignment->equipment,
            );
        }

        if (QueueFuelVerificationService::currentVerification($orderProduct) === null) {
            return self::blocked(
                self::CODE_FUEL_REQUIRED,
                sprintf(
                    'Fuel Full must be verified for equipment %s before it can leave the yard.',
                    $assignment->equipment->equipment_name,
                ),
                $orderProduct,
                $assignment->equipment,
            );
        }

        return null;
    }

    /** Under active Queue Line management = enforceable. */
    private static function isQueueManaged(OrderProduct $orderProduct): bool
    {
        $queueItem = $orderProduct->queueLineItem;

        if ($queueItem?->suppressed_forever) {
            return false; // removed from Queue Line management (approved policy)
        }

        if ($queueItem?->completed_at !== null) {
            return false; // already left — repeats/late saves flow freely
        }

        // A financially inactive order (voided-out / fully refunded) never
        // appears on the board — the guard must not enforce for it either
        // (no ghost enforcement for invisible items). Cheap suspect screen
        // first so ordinary releases never pay for the full summary.
        $order = $orderProduct->order;
        if ($order) {
            $suspect = $order->payments()
                ->whereIn('status', \App\Services\Orders\OrderFinancialActivity::SUSPECT_PAYMENT_STATUSES)
                ->exists();

            if ($suspect && ! \App\Services\Orders\OrderFinancialActivity::isActive($order)) {
                return false;
            }
        }

        return QueueLineEligibility::eligibleQuery()
            ->whereKey($orderProduct->id)
            ->exists();
    }

    private static function blocked(string $code, string $message, OrderProduct $orderProduct, ?Equipment $equipment): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'order_product_unique_id' => $orderProduct->unique_id,
            'equipment' => $equipment ? [
                'unique_id' => $equipment->unique_id,
                'equipment_id' => $equipment->equipment_id,
                'name' => $equipment->equipment_name,
            ] : null,
            'fuel_state' => $code === self::CODE_FUEL_REQUIRED || $code === self::CODE_EQUIPMENT_REQUIRED
                ? 'not_verified'
                : null,
            'corrective_action' => match ($code) {
                self::CODE_EQUIPMENT_REQUIRED => 'Assign equipment on the Queue Line, verify Fuel Full, then release.',
                self::CODE_ASSIGNMENT_CHANGED => 'Refresh the item and release the machine currently staged on the Queue Line.',
                default => 'Verify Fuel Full on the Queue Line for this machine, then retry the release.',
            },
        ];
    }
}
