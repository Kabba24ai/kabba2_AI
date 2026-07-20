<?php

namespace App\Listeners\QueueLine;

use App\Events\Admin\Orders\OrderProductDriverChecklistUpdated;
use App\Models\Orders\OrderProduct;
use App\Services\QueueLine\QueueLineService;

/**
 * Truck-path Queue Line completion: the driver's "Ready to Go" action on
 * the DELIVERY leg is the approved moment the equipment leaves the yard.
 * The event fires inside the driver-checklist transaction, so a listener
 * failure rolls the whole action back — completion and release stay atomic.
 *
 * Idempotent by the completion null-latch: a repeated Ready to Go replays
 * without moving the original completion.
 */
class CompleteOnDispatchStart
{
    public function handle(OrderProductDriverChecklistUpdated $event): void
    {
        $requested = $event->data['requested_data'] ?? [];

        // Delivery leg reaching Ready to Go — the release signal. (The
        // status string is authoritative; ready_to_go_at rides along.)
        $isDeliveryReadyToGo = ($requested['delivery_equipment_driver_status'] ?? null) === 'Ready to Go'
            || array_key_exists('delivery_ready_to_go_at', $requested);

        if (! $isDeliveryReadyToGo) {
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
