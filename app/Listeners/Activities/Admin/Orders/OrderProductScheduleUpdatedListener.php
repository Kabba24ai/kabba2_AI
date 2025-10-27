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
