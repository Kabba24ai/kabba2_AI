<?php
namespace App\Listeners\Activities\Admin\Orders;

use App\Events\Admin\Orders\RefundInitiateEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundOperationStatus;
use App\Services\PaymentDescriptionPresenter;

class RefundInitiateListener
{
    /**
     * Handle the event.
     *
     * Phase 3C: refund_operation_status (whether THIS refund event's own
     * allocation attempts actually succeeded) now takes priority over the
     * refund row's OrderPaymentStatus (whether the ORDER still has a
     * refundable balance) for deciding which history action to log — a
     * partially or fully failed refund event must never be recorded as a
     * plain "Order Refunded"/"Order Partial Refund" success. Exactly one
     * history row is written per event; the old separate "Refund failed"
     * row (which only ever fired for a status this flow could never
     * actually produce pre-Phase-3C) is gone — RefundFailed below is that
     * row now, written from the same single, presenter-driven description.
     */
    public function handle(RefundInitiateEvent $event)
    {
        $order = $event->order;
        $user = $event->user;
        $payment = $event->payment;

        $action = match (true) {
            $payment->refund_operation_status === RefundOperationStatus::Failed => OrderHistoryAction::RefundFailed,
            $payment->refund_operation_status === RefundOperationStatus::PartiallyCompleted => OrderHistoryAction::RefundPartiallyCompleted,
            $payment->status === OrderPaymentStatus::Refund => OrderHistoryAction::OrderRefunded,
            default => OrderHistoryAction::OrderPartialRefund,
        };

        $description = PaymentDescriptionPresenter::refundHistoryDescription($payment);

        $order->history()->create([
            'customer_id' => $order->customer_id,
            'order_payment_id' => $payment->id,
            'user_id' => $user ? $user->id : null,
            'action_by' => OrderHistoryActionBy::User,
            'action_date' => now(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}
