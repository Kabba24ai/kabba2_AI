<?php
namespace App\Listeners\Activities\Front\Checkout;

use App\Events\Front\Checkout\OrderPlacedEvent;

// enums
use App\Enums\Orders\OrderHistoryAction;
use App\Enums\Orders\OrderHistoryActionBy;
use App\Enums\Orders\OrderPaymentStatus;
use App\Services\PaymentDescriptionPresenter;
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

        $employeeName = $employee?->full_name ?? 'System';
        $customerName = $customer?->full_name ?? 'Customer';

        $message = match($orderActionType) {
            'reorder' => "Reorder by {$employeeName}",
            'website_login' => "Website Login by {$employeeName}",
            'master_passcode' => "Admin code by {$employeeName}",
            'new_account' => "New Account by {$employeeName}",
            'customer_account_login' => "by Customer with Account login",
            'customer_no_account' => "by Customer with no account",
            default => "by {$customerName}",
        };

        $order->history()->create([
            'customer_id' => $customer->id,
            'user_id' => ($employee) ? $employee->id : null,
            'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
            'action_date' => now(),
            'action' => OrderHistoryAction::CreateOrder,
            'description' => "Order {$order->order_number} placed - ".$message,
        ]);

        // Determine payment action; description always comes from the
        // centralized presenter so it reflects the payment actually taken
        // (not an assumption that every completed payment was by card).
        // A failed payment still logs this first entry as "initiated" —
        // the dedicated PaymentFailed entry below carries the outcome,
        // preserving the original two-entry (attempt + outcome) trail.
        $action = match (true) {
            $payment->status === OrderPaymentStatus::PartialPayment => OrderHistoryAction::PartialPaymentReceived,
            $payment->status->isSettled() => OrderHistoryAction::OrderPaid,
            default => OrderHistoryAction::PaymentInitiated,
        };

        $order->history()->create([
            'customer_id' => $customer->id,
            'user_id' => ($employee) ? $employee->id : null,
            'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
            'action_date' => now(),
            'action' => $action,
            'description' => PaymentDescriptionPresenter::historyDescription($payment),
        ]);

        if ($payment->status->isFailed()) {
            $order->history()->create([
                'customer_id' => $customer->id,
                'user_id' => ($employee) ? $employee->id : null,
                'action_by' => ($employee) ? OrderHistoryActionBy::User : OrderHistoryActionBy::Customer,
                'action_date' => now(),
                'action' => OrderHistoryAction::PaymentFailed,
                'description' => PaymentDescriptionPresenter::failureDescription($payment),
            ]);
        }
    }
}
