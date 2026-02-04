<?php
namespace App\Listeners\Activities\Front\Checkout;

use App\Events\Front\Checkout\OrderPlacedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateOrderListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event)
    {
        $order = $event->order;
        $customer = $event->customer;
        $payment = $event->payment;
        $employee = $event->employee;
        $orderActionType = $event->orderActionType;

        $message = match($orderActionType) {
            'reorder' => "Reorder by {$employee->full_name}",
            'website_login' => "Website Login by {$employee->full_name}",
            'master_passcode' => "Master Passcode by {$employee->full_name}",
            'new_account' => "New Account by {$employee->full_name}",
            'customer_account_login' => "by Customer with Account login",
            'customer_no_account' => "by Customer with no account",
            default => "by {$customer->full_name}",
        };

        $order->history()->create([
            'customer_id' => $customer->id,
            'user_id' => ($employee) ? $employee->id : null,
            'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
            'action_date' => now(),
            'action' => OrderHistoryAction::CreateOrder,
            'description' => "Order {$order->order_number} placed - ".$message,
        ]);

        // Determine payment action and description
        // if ($payment->payment_method === OrderPaymentMethod::Card && $payment->status->isPaid()) {
        //     $action = OrderHistoryAction::OrderPaid;
        //     $description = "Paid In Full Via - Credit/Debit Card";
        // } else {
        //     $action = OrderHistoryAction::PaymentInitiated;
        //     $description = "Payment initiated via {$payment->payment_method->label()}";
        // }

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

        $order->history()->create([
            'customer_id' => $customer->id,
            'user_id' => ($employee) ? $employee->id : null,
            'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
            'action_date' => now(),
            'action' => $action,
            'description' => $description,
        ]);

        if ($payment->status->isFailed()) {
            $order->history()->create([
                'customer_id' => $customer->id,
                'user_id' => ($employee) ? $employee->id : null,
                'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
                'action_date' => now(),
                'action' => OrderHistoryAction::PaymentFailed,
                'description' => "Payment failed via {$payment->payment_method->label()}",
            ]);
        }
    }
}
