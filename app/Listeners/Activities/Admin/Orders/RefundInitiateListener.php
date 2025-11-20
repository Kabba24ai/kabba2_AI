<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\RefundInitiateEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
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

         // Determine payment action and description
        if ($payment->payment_method === OrderPaymentMethod::Card && $payment->status->isFullRefund()) {
            $action = OrderHistoryAction::OrderRefunded;
            $description = "Full refund processed";
        }else{
            $action = OrderHistoryAction::OrderPartialRefund;
            $description = "Partial refund processed";
        }

        $order->history()->create([
            'customer_id' => $order->customer_id,
            'user_id' => $user ? $user->id : null,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $action,
            'description' => $description,
        ]);

        if ($payment->status->isFailed()) {
            $order->history()->create([
                'customer_id' => $order->customer_id,
                'user_id' => $user ? $user->id : null,
                'action_by' => OrderHistoryActionBy::User,
                'action_date' => now(),
                'action' => OrderHistoryAction::PaymentFailed,
                'description' => "Refund failed",
            ]);
        }

    }
}
