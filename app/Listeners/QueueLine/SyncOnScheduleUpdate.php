<?php

namespace App\Listeners\QueueLine;

use App\Events\Admin\Orders\OrderProductScheduleUpdated;
use App\Models\Orders\OrderProduct;
use App\Services\QueueLine\QueueLineService;

/**
 * Canonical schedule-driven Queue Line reconciliation (2026-07-23).
 *
 * Fires on OrderProductScheduleUpdated — the shared event every delivery/return
 * status change dispatches (admin Order Details, API schedule update, dispatch
 * status, assign-and-complete). It keeps the board in step with the ORDER's
 * canonical delivery status, the mission's source of truth for "Equipment
 * Delivered" — not Queue Line history:
 *
 *   • delivery → Completed .......... complete() ⇒ Equipment Delivered for the
 *       rest of the day, however it got there (direct pickup, walk-in Fast
 *       Track, administrative completion). Never-staged items still complete;
 *       the skipped staging is recorded (FAST TRACK), never prevented.
 *   • delivery → Reschedule ......... requeue() ⇒ leaves the board entirely and
 *       must be fully re-staged if it returns.
 *   • delivery → Pending (reversal).. requeue() ⇒ returns to Pending and must
 *       be re-staged, as though it never left the yard.
 *   • delivery → Close as Completed . ignored — administrative closure of a
 *       cancelled / never-shipped order never reaches Equipment Delivered.
 *
 * Acts ONLY on an EXPLICIT delivery-leg status change carried in the event's
 * requested_data. This is deliberate:
 *   - editing a delivery time (or any non-status field) never re-stamps or
 *     re-opens anything — so re-saving a long-completed order can never
 *     back-date it into today's Equipment Delivered;
 *   - the truck path is untouched here: a driver going "On My Way" fires
 *     OrderProductDriverChecklistUpdated (CompleteOnDispatchStart), not this
 *     event, and an en-route truck legitimately sits at delivery_status =
 *     Pending with a dispatch completion latch (the Pending branch below never
 *     re-opens a dispatch-completed item).
 *
 * Idempotent: complete() is a one-way null-latch, requeue() is a plain clear.
 * Runs alongside the existing OrderProductScheduleUpdatedListener (order-history
 * writer) — both are auto-discovered by their event type-hint.
 */
class SyncOnScheduleUpdate
{
    public function handle(OrderProductScheduleUpdated $event): void
    {
        $deliveryStatus = $this->requestedDeliveryStatus($event->data['requested_data'] ?? []);

        if ($deliveryStatus === null) {
            return; // not a delivery-leg status change — nothing to reconcile
        }

        $orderProduct = $this->resolveOrderProduct($event);

        if (! $orderProduct || ($orderProduct->product_data['product_type'] ?? null) !== 'Rental') {
            return;
        }

        if ($deliveryStatus === 'Completed') {
            QueueLineService::complete(
                $orderProduct,
                QueueLineService::VIA_SCHEDULE_COMPLETED,
                $orderProduct->softAssignment?->equipment_id ?? $orderProduct->equipment_id,
            );

            return;
        }

        if ($deliveryStatus === 'Reschedule') {
            QueueLineService::requeue($orderProduct);

            return;
        }

        if ($deliveryStatus === 'Pending') {
            // Administrative reversal (Completed → Pending): return to Pending
            // and require re-staging. Never touch a truck that is en route — it
            // sits at Pending with a dispatch completion latch by design.
            $item = $orderProduct->queueLineItem;

            if ($item
                && $item->completed_at !== null
                && $item->completed_via !== QueueLineService::VIA_DISPATCH_STARTED) {
                QueueLineService::requeue($orderProduct);
            }
        }

        // 'Close as Completed' and anything else: intentionally no queue action.
    }

    /**
     * The intended DELIVERY-leg status from the heterogeneous requested_data
     * payloads, or null when the change did not target the delivery status:
     *   - admin / API schedule / dispatch: ['type' => 'delivery', 'delivery_status' => X]
     *   - assign-and-complete:             ['type' => 'delivery', 'status' => 'Completed']
     * A pickup/return change ('pickup_status', or type 'return') returns null.
     */
    private function requestedDeliveryStatus(array $requested): ?string
    {
        if (array_key_exists('delivery_status', $requested)) {
            return $requested['delivery_status'];
        }

        $type = strtolower((string) ($requested['type'] ?? ''));

        if ($type === 'delivery' && array_key_exists('status', $requested)) {
            return $requested['status'];
        }

        return null;
    }

    private function resolveOrderProduct(OrderProductScheduleUpdated $event): ?OrderProduct
    {
        $id = $event->data['order_product']['id'] ?? null;

        if (! $id) {
            return null;
        }

        return OrderProduct::with(['queueLineItem', 'softAssignment'])->find($id);
    }
}
