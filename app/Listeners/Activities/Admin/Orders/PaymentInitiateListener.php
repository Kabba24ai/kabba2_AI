<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\PaymentInitiateEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Services\PaymentDescriptionPresenter;

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

        $amountDisplay = '$' . number_format((float) $payment->amount, 2);
        $personName    = $user?->full_name ?? 'Unknown';

        // Determine payment action; description always comes from the
        // centralized presenter so it reflects the payment actually taken
        // (not an assumption that every completed payment was by card). A
        // settled Store Credit payment gets its own action so the Payment
        // Timeline reads "Store Credit applied" rather than "Payment
        // received via Store Credit" — see PaymentDescriptionPresenter::timelineEntry().
        $isStoreCredit = $payment->payment_method === OrderPaymentMethod::StoreCredit;

        if ($payment->status === OrderPaymentStatus::PartialPayment) {
            $action      = $isStoreCredit ? OrderHistoryAction::StoreCreditApplied : OrderHistoryAction::PartialPaymentReceived;
            $methodLabel = PaymentDescriptionPresenter::methodLabel($payment->payment_method);
            $description = "Partial Payment: {$methodLabel} | {$amountDisplay} | {$personName}";
        } elseif ($payment->status->isSettled()) {
            $action      = $isStoreCredit ? OrderHistoryAction::StoreCreditApplied : OrderHistoryAction::OrderPaid;
            $description = PaymentDescriptionPresenter::historyDescription($payment);
        } else {
            $action      = OrderHistoryAction::PaymentInitiated;
            $description = PaymentDescriptionPresenter::historyDescription($payment);
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
                'description' => PaymentDescriptionPresenter::failureDescription($payment),
            ]);
        }

    }
}
