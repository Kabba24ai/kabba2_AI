<?php

namespace App\Listeners\QueueLine;

use App\Events\Admin\Orders\OrderProductDriverChecklistUpdated;
use App\Models\Orders\OrderProduct;
use App\Services\QueueLine\QueueLineService;

/**
 * Truck-path Queue Line completion: the driver's "On My Way" action
 * ("Load Map & Go") on the DELIVERY leg is the approved moment the equipment
 * actually leaves the yard (2026-07-23). "Ready to Go" is only prep (fuel,
 * keys, attachments) and no longer completes the item — drivers routinely
 * press it before departure. The event fires inside the driver-checklist
 * transaction, so a listener failure rolls the whole action back.
 *
 * Idempotent by the completion null-latch: a repeated departure — or a later
 * "Arrived" for a driver who skipped straight past On My Way — replays without
 * moving the original completion.
 */
class CompleteOnDispatchStart
{
    public function handle(OrderProductDriverChecklistUpdated $event): void
    {
        $requested = $event->data['requested_data'] ?? [];

        // Delivery leg departing the yard — the release signal. "On My Way"
        // is the primary trigger; "Arrived" is a safety net for a flow that
        // skipped it. (The status string is authoritative; the *_at
        // timestamps ride along.)
        $deliveryStatus = $requested['delivery_equipment_driver_status'] ?? null;
        $isDeliveryDeparted = in_array($deliveryStatus, ['On My Way', 'Arrived'], true)
            || array_key_exists('delivery_on_my_way_at', $requested)
            || array_key_exists('delivery_arrived_at', $requested);

        if (! $isDeliveryDeparted) {
            return;
        }

        $orderProductId = $event->data['order_product']['id'] ?? null;

        if (! $orderProductId) {
            return;
        }

        $orderProduct = OrderProduct::with('softAssignment', 'queueLineItem')->find($orderProductId);

        // Outbound rental rows only — the Queue Line's unit of work
        if (! $orderProduct || ($orderProduct->product_data['product_type'] ?? null) !== 'Rental') {
            return;
        }

        QueueLineService::complete(
            $orderProduct,
            QueueLineService::VIA_DISPATCH_STARTED,
            $orderProduct->softAssignment?->equipment_id ?? $orderProduct->equipment_id,
        );
    }
}
