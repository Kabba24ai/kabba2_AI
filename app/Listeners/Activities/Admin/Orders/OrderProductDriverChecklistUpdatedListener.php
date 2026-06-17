<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderProductDriverChecklistUpdated;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class OrderProductDriverChecklistUpdatedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderProductDriverChecklistUpdated $event): void
    {
        $order = $event->order;
        $user  = $event->employee;
        $data  = $event->data;

        $updated = collect($data['requested_data'])
            ->map(fn($v, $k) => ucwords(str_replace('_', ' ', $k)) . ': ' . $v)
            ->values()
            ->implode(', ');

        $message = "{$user?->full_name} updated driver checklist ({$updated}) for {$data['order_product']['product_name']}.";

        $order->history()->create([
            'customer_id' => null,
            'user_id'     => $user?->id,
            'action_by'   => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action'      => OrderHistoryAction::DriverChecklistUpdated,
            'description' => $message,
            'extras'      => json_encode($data),
        ]);
    }
}
