<?php

namespace App\Listeners\QueueLine;

use App\Events\Admin\Orders\OrderCustomerChecklistEvent;
use App\Models\Orders\OrderProduct;
use App\Services\QueueLine\QueueLineService;

/**
 * Customer-checklist Queue Line sync — two responsibilities:
 *
 * 1. checklist_delivery — the in-store release moment AND the truck-path
 *    defensive second check. The event carries only the ORDER (audited
 *    gap), so we sweep its rental rows whose delivery leg is now Completed
 *    and latch any open queue items. The sweep + null-latch make the
 *    second check inherently idempotent: an item already completed by
 *    dispatch-start replays silently; no duplicate completion history.
 *
 * 2. checklist_removed — the only supported operational reversal (the
 *    checklist RemoveController returns every row on the order to a
 *    Pending outbound state). Completed queue items REOPEN; fuel must be
 *    re-verified because the hard-assign destroyed the soft-assignment
 *    episode (a fresh assignment = fresh episode = fresh sign-off).
 */
class SyncOnCustomerChecklist
{
    public function handle(OrderCustomerChecklistEvent $event): void
    {
        if ($event->type === 'checklist_delivery') {
            OrderProduct::where('order_id', $event->order->id)
                ->where('product_data->product_type', 'Rental')
                ->where('delivery_status', 'Completed')
                ->with('queueLineItem')
                ->get()
                ->each(fn (OrderProduct $row) => QueueLineService::complete(
                    $row,
                    QueueLineService::VIA_CUSTOMER_CHECKLIST_COMPLETED,
                    $row->equipment_id, // the hard-assigned unit that left
                ));

            return;
        }

        if ($event->type === 'checklist_removed') {
            OrderProduct::where('order_id', $event->order->id)
                ->with('queueLineItem')
                ->get()
                ->each(fn (OrderProduct $row) => QueueLineService::reopen($row));
        }
    }
}
