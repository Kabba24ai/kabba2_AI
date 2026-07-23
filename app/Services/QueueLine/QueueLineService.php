<?php

namespace App\Services\QueueLine;

use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineItem;
use InvalidArgumentException;

/**
 * The ONLY writer of queue_line_items. Every state change flows through
 * here; rows are created lazily via updateOrCreate on the unique
 * order_product_id (duplicate actions can never create duplicate rows —
 * the DB unique constraint is the backstop).
 *
 * State semantics (approved):
 *  - Not Staged     = no row, or staged_at null
 *  - On Queue Line  = staged_at set (actor + timestamp; null-latch: re-staging
 *                     an already-staged item keeps the original stamp)
 *  - RUSH           = rush_at set; item-specific, never order-wide
 *  - Remove Today   = suppressed_on = today (self-expires when the
 *                     operational day changes; no scheduler)
 *  - Remove Forever = suppressed_forever flag; survives reschedules; dies
 *                     with the order_product row; reversible via restore()
 */
final class QueueLineService
{
    /** Controlled completed_via values — never free text. */
    public const VIA_DISPATCH_STARTED = 'dispatch_started';

    public const VIA_CUSTOMER_CHECKLIST_COMPLETED = 'customer_checklist_completed';

    /**
     * Canonical delivery completion recorded off a schedule status change
     * (2026-07-23): an admin/API/dispatch flip of the delivery leg to
     * Completed / Close as Completed — direct pickup, walk-in Fast Track, or
     * administrative completion. Widens "Equipment Delivered" to everything
     * that left the yard today, not just items that went through staging.
     */
    public const VIA_SCHEDULE_COMPLETED = 'schedule_completed';

    /**
     * Completion latch (Phase 3C): the equipment physically left the yard.
     * One-way and idempotent — a repeat call NEVER moves the original
     * timestamp, source, or equipment; only an explicit reopen() resets it.
     * The sidecar row, fuel history, and reassignment history all survive.
     */
    public static function complete(OrderProduct $orderProduct, string $via, ?int $equipmentId): QueueLineItem
    {
        if (! in_array($via, [self::VIA_DISPATCH_STARTED, self::VIA_CUSTOMER_CHECKLIST_COMPLETED, self::VIA_SCHEDULE_COMPLETED], true)) {
            throw new QueueLineOperationException("Unknown Queue Line completion source: {$via}.");
        }

        $item = self::row($orderProduct);

        if ($item->completed_at === null) { // null-latch idempotency
            $item->forceFill([
                'completed_at' => now(),
                'completed_via' => $via,
                'completed_equipment_id' => $equipmentId,
            ])->save();
        }

        return $item;
    }

    public static function isCompleted(OrderProduct $orderProduct): bool
    {
        return $orderProduct->queueLineItem?->completed_at !== null;
    }

    /**
     * Reopen after a legitimate operational reversal (today: customer
     * checklist removal, which returns the delivery leg to Pending). The
     * latch clears so the item can re-enter the board; fuel must be
     * re-verified because the assignment episode was destroyed by the
     * hard-assign (a fresh soft assignment = fresh episode = fresh fuel).
     * The single-latch schema keeps no completion history — documented.
     */
    public static function reopen(OrderProduct $orderProduct): ?QueueLineItem
    {
        $item = QueueLineItem::where('order_product_id', $orderProduct->id)->first();

        if (! $item || $item->completed_at === null) {
            return $item;
        }

        $item->forceFill([
            'completed_at' => null,
            'completed_via' => null,
            'completed_equipment_id' => null,
        ])->save();

        return $item;
    }

    /**
     * Return an item to Queue Line — Pending as though it never left the yard
     * (2026-07-23). Used when an administrator reverses a delivery
     * (Completed → Pending) or reschedules it: the completion latch clears AND
     * the staged latch clears, so the item re-enters the Pending column and the
     * full staging process is required again before it can leave. No-op when no
     * sidecar row exists (nothing ever happened to the item).
     */
    public static function requeue(OrderProduct $orderProduct): ?QueueLineItem
    {
        $item = QueueLineItem::where('order_product_id', $orderProduct->id)->first();

        if (! $item) {
            return null;
        }

        $item->forceFill([
            'completed_at' => null,
            'completed_via' => null,
            'completed_equipment_id' => null,
            'staged_at' => null,
            'staged_by' => null,
        ])->save();

        return $item;
    }

    public static function stage(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        // A physical machine must be selected before it can be ON the line.
        // (Needs Equipment Assignment rows expose no stage control, but the
        // service is the rule's owner, not the UI.)
        if (! $orderProduct->softAssignment?->equipment) {
            throw new InvalidArgumentException(
                'This item has no assigned equipment yet — assign a machine before staging it on the Queue Line.'
            );
        }

        $item = self::row($orderProduct);

        if ($item->staged_at === null) { // null-latch: first stage wins
            $item->forceFill(['staged_at' => now(), 'staged_by' => $actor->id])->save();
        }

        return $item;
    }

    public static function unstage(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        $item = self::row($orderProduct);
        $item->forceFill(['staged_at' => null, 'staged_by' => null])->save();

        return $item;
    }

    public static function rush(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        $item = self::row($orderProduct);

        if ($item->rush_at === null) {
            $item->forceFill(['rush_at' => now(), 'rush_by' => $actor->id])->save();
        }

        return $item;
    }

    public static function unrush(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        $item = self::row($orderProduct);
        $item->forceFill(['rush_at' => null, 'rush_by' => null])->save();

        return $item;
    }

    /** Remove Today: hidden only while suppressed_on = the current operational day. */
    public static function removeToday(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        $item = self::row($orderProduct);
        $item->forceFill([
            'suppressed_on' => today(),
            'suppressed_on_by' => $actor->id,
        ])->save();

        return $item;
    }

    public static function removeForever(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        $item = self::row($orderProduct);
        $item->forceFill([
            'suppressed_forever' => true,
            'suppressed_forever_at' => now(),
            'suppressed_forever_by' => $actor->id,
        ])->save();

        return $item;
    }

    /** Deliberate un-suppress — Remove Forever is never irreversible from the UI. */
    public static function restore(OrderProduct $orderProduct, User $actor): QueueLineItem
    {
        $item = self::row($orderProduct);
        $item->forceFill([
            'suppressed_forever' => false,
            'suppressed_forever_at' => null,
            'suppressed_forever_by' => null,
        ])->save();

        return $item;
    }

    /**
     * Lazy one-row-per-item accessor — the unique constraint backs this up.
     * A soft-deleted row is restored rather than duplicated (the unique
     * index spans trashed rows too).
     */
    private static function row(OrderProduct $orderProduct): QueueLineItem
    {
        $item = QueueLineItem::withTrashed()
            ->where('order_product_id', $orderProduct->id)
            ->first();

        if ($item) {
            if ($item->trashed()) {
                $item->restore();
            }

            return $item;
        }

        return QueueLineItem::create([
            'order_product_id' => $orderProduct->id,
            'order_id' => $orderProduct->order_id,
        ]);
    }
}
