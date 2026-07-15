<?php

namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\PaymentAddedToAccountEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class PaymentAddedToAccountListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentAddedToAccountEvent $event)
    {
        $order = $event->order;
        $user = $event->user;
        $payment = $event->payment;

        $order->history()->create([
            'customer_id' => $order->customer_id,
            'order_payment_id' => $payment->id,
            'user_id' => $user?->id,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => OrderHistoryAction::AddedToAccount,
            'description' => "Order {$order->order_number} added to  account  by {$user->full_name}",
            'extras' => json_encode($payment),
        ]);
    }
}
