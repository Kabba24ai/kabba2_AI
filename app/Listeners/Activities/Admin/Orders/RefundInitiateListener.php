<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\RefundInitiateEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentStatus;

class RefundInitiateListener
{
    /**
     * Handle the event.
     */
    public function handle(RefundInitiateEvent $event)
    {
        $order = $event->order;
        $user = $event->user;
        $payment = $event->payment;

        // Determine payment action and description from the refund row's
        // own status — not payment_method === Card, which mis-classified
        // a full refund on any other method as "Partial refund processed."
        if ($payment->status === OrderPaymentStatus::Refund) {
            $action = OrderHistoryAction::OrderRefunded;
            $description = "Full refund processed";
        } else {
            $action = OrderHistoryAction::OrderPartialRefund;
            $description = "Partial refund processed";
        }

        $order->history()->create([
            'customer_id' => $order->customer_id,
            'order_payment_id' => $payment->id,
            'user_id' => $user ? $user->id : null,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $action,
            'description' => $description,
        ]);

        if ($payment->status->isFailed()) {
            $order->history()->create([
                'customer_id' => $order->customer_id,
                'order_payment_id' => $payment->id,
                'user_id' => $user ? $user->id : null,
                'action_by' => OrderHistoryActionBy::User,
                'action_date' => now(),
                'action' => OrderHistoryAction::PaymentFailed,
                'description' => "Refund failed",
            ]);
        }

    }
}
