<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\PaymentConfirmedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;

class PaymentConfirmedListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentConfirmedEvent $event)
    {
        $order = $event->order;
        $user = $event->user;
        $payment = $event->payment;

        $order->history()->create([
            'customer_id' => $order->customer_id,
            'user_id' => $user ? $user->id : null,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => OrderHistoryAction::OrderPaid,
            'description' => "Payment confirmed for order {$order->order_number} by {$user->full_name}",
            'extras' => json_encode($payment),
        ]);
    }
}
