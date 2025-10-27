<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\OrderAddressUpdatedEvent;

// enums
use App\Enums\Orders\OrderAddressType;
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class OrderAddressUpdatedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderAddressUpdatedEvent $event)
    {
        $order = $event->order;
        $user = $event->employee;
        $type = $event->type;

        $typeEnum = OrderAddressType::from($type);

        $typeAction = match ($typeEnum) {
            OrderAddressType::Billing => OrderHistoryAction::BillingAddressUpdated,
            OrderAddressType::Delivery => OrderHistoryAction::DeliveryAddressUpdated,
            default => OrderHistoryAction::DeliveryAddressUpdated,
        };

        $message = match ($typeEnum) {
            OrderAddressType::Billing => "{$user->full_name} updated billing address.",
            OrderAddressType::Delivery => "{$user->full_name} updated delivery address.",
            default => "{$user->full_name} updated address.",
        };

        $order->history()->create([
            'customer_id' => null,
            'user_id' => $user?->id,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $typeAction,
            'description' => $message,
        ]);
    }
}
