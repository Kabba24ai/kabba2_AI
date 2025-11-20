<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\PaymentInitiateEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;

class PaymentInitiateListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentInitiateEvent $event)
    {
        $order = $event->order;
        $user = $event->user;
        $payment = $event->payment;

         // Determine payment action and description
        if ($payment->payment_method === OrderPaymentMethod::Card && $payment->status->isPaid()) {
            $action = OrderHistoryAction::OrderPaid;
            $description = "Paid In Full Via - Credit/Debit Card";
        } else {
            $action = OrderHistoryAction::PaymentInitiated;
            $description = "Payment initiated via {$payment->payment_method->label()}";
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
                'description' => "Payment failed via {$payment->payment_method->label()}",
            ]);
        }

    }
}
