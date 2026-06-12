<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\PaymentInitiateEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;

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

        $methodLabel   = $payment->payment_method?->label() ?? 'Unknown';
        $amountDisplay = '$' . number_format((float) $payment->amount, 2);
        $personName    = $user?->full_name ?? 'Unknown';

        // Determine payment action and description
        if ($payment->status === OrderPaymentStatus::PartialPayment) {
            $action      = OrderHistoryAction::PartialPaymentReceived;
            $description = "Partial Payment: {$methodLabel} | {$amountDisplay} | {$personName}";
        } elseif ($payment->payment_method === OrderPaymentMethod::Card && $payment->status->isPaid()) {
            $action      = OrderHistoryAction::OrderPaid;
            $description = "Paid In Full Via - Credit/Debit Card";
        } else {
            $action      = OrderHistoryAction::PaymentInitiated;
            $description = "Payment initiated via {$methodLabel}";
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
