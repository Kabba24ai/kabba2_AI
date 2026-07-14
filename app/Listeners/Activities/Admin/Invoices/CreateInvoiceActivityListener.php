<?php

namespace App\Listeners\Activities\Admin\Invoices;

use App\Events\Admin\Invoices\InvoicePaidEvent;

// Enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Services\PaymentDescriptionPresenter;

use Illuminate\Support\Facades\Log;

class CreateInvoiceActivityListener
{
    /** 
     * Handle the event.
     */
    public function handle(InvoicePaidEvent $event)
    {
        $order = $event->order;
        $customer = $event->customer;
        $payment = $event->payment;
        $employee = $event->employee;

        
        Log::info('InvoicePaidEvent received in CreateInvoiceActivityListener', [
            'order_id'     => $order->id,
            'customer_id'  => $customer->id,
            'payment_id'   => $payment->id,
            'payment_status' => $payment->status,
            'handled_by_admin' => $employee ? true : false,
        ]);

        // Determine payment action; description always comes from the
        // centralized presenter so it reflects the payment actually taken
        // (not a hand-typed paraphrase that can drift from the enum).
        $isStoreCredit = $payment->payment_method === OrderPaymentMethod::StoreCredit;
        $action = match (true) {
            $isStoreCredit && $payment->status->isSettled() => OrderHistoryAction::StoreCreditApplied,
            $payment->status->isSettled() => OrderHistoryAction::OrderPaid,
            default => OrderHistoryAction::PaymentInitiated,
        };
        $description = PaymentDescriptionPresenter::historyDescription($payment);

        
        Log::debug('Logging order history entry for payment', [
            'action' => $action->value ?? $action,
            'description' => $description,
        ]);

        $order->history()->create([
            'customer_id' => $customer->id,
            'order_payment_id' => $payment->id,
            'user_id' => ($employee) ? $employee->id : null,
            'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
            'action_date' => now(),
            'action' => $action,
            'description' => $description,
        ]);

        if ($payment->status->isFailed()) {

                Log::warning('Payment failed while creating activity log', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);

            $order->history()->create([
                'customer_id' => $customer->id,
                'order_payment_id' => $payment->id,
                'user_id' => ($employee) ? $employee->id : null,
                'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
                'action_date' => now(),
                'action' => OrderHistoryAction::PaymentFailed,
                'description' => PaymentDescriptionPresenter::failureDescription($payment),
            ]);
        }

          Log::info('Invoice activity log successfully created for order.', [
            'order_id' => $order->id,
        ]);
    }
}
