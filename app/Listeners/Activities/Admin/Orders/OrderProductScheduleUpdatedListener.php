<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderProductScheduleUpdated;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class OrderProductScheduleUpdatedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderProductScheduleUpdated $event)
    {
        $order = $event->order;
        $user = $event->employee;
        $data = $event->data;
        $typeAction = OrderHistoryAction::ProductScheduleUpdated;

        $field = collect($data['requested_data'])
            ->except('type')
            ->keys()
            ->first();
        $field = str_replace('_', ' ', ucfirst($field));
        $message = "{$user?->full_name} updated product schedule ({$field}) for {$data['order_product']['product_name']}.";

        // The order_product snapshot carries full equipment/checklist/product
        // relation data (assigned equipment's rental-ready checklist tree,
        // product category HTML, etc.) baked into product_data/equipment_details
        // by the callers that build this event's payload. None of that is read
        // back anywhere for this history entry — drop it so a single delivery
        // status flip on equipment with a large checklist template doesn't
        // overflow the extras column (was: SQLSTATE[22001] truncation).
        $data['order_product'] = collect($data['order_product'])
            ->except(['product_data', 'equipment_details', 'dispatch_checklist'])
            ->all();

        $order->history()->create([
            'customer_id' => null,
            'user_id' => $user?->id,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $typeAction,
            'description' => $message,
            'extras' => json_encode($data),
        ]);
    }
}
