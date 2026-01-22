<?php

namespace App\Listeners\Activities\Admin\Invoices;

use App\Events\Admin\Invoices\InvoicePaidEvent;

// Enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentStatus;

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

        // Determine payment action and description
        if ($payment->status->isPaid() || $payment->status->isInvoice()) {
            $action = OrderHistoryAction::OrderPaid;
            $description = match ($payment->status) {
                OrderPaymentStatus::Paid => "Paid In Full Via - Credit/Debit Card",
                OrderPaymentStatus::InvoiceCard => "Paid Invoice Via CC on File From Customer Dashboard",
                OrderPaymentStatus::InvoiceCash => "Paid Invoice Via Front Desk From Admin Panel",
                OrderPaymentStatus::InvoiceOnline => "Paid Invoice Via Direct Bank From Admin Panel",
                OrderPaymentStatus::InvoiceCheque => "Paid Invoice Via Check From Admin Panel",
                OrderPaymentStatus::InvoiceOther => "Paid Invoice Via Other Method From Admin Panel",
                default => "Paid In Full",
            };
        } else {
            $action = OrderHistoryAction::PaymentInitiated;
            $methodLabel = is_object($payment->payment_method) && method_exists($payment->payment_method, 'label')
                ? $payment->payment_method->label()
                : ucfirst($payment->payment_method);
            $description = "Payment initiated via {$methodLabel}";
        }

        
        Log::debug('Logging order history entry for payment', [
            'action' => $action->value ?? $action,
            'description' => $description,
        ]);

        $order->history()->create([
            'customer_id' => $customer->id,
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
                'user_id' => ($employee) ? $employee->id : null,
                'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
                'action_date' => now(),
                'action' => OrderHistoryAction::PaymentFailed,
                'description' => "Payment failed via {$payment->payment_method->label()}",
            ]);
        }

          Log::info('Invoice activity log successfully created for order.', [
            'order_id' => $order->id,
        ]);
    }
}
