<?php
namespace App\Listeners\Activities\Front\Checkout;

use App\Events\Front\Checkout\OrderTermsSignedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class OrderTermsSignedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderTermsSignedEvent $event)
    {
        $order = $event->order;

        $order->history()->create([
            'customer_id' => $order->customer_id,
            'user_id' => null,
            'action_by' => OrderHistoryActionBy::Customer,
            'action_date' => now(),
            'action' => OrderHistoryAction::TermsSigned,
            'description' => "Order {$order->order_number} terms and conditions signed by customer",
        ]);

    }
}
