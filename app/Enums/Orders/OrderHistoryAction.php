<?php

namespace App\Enums\Orders;

enum OrderHistoryAction : string
{
    case CreateOrder = 'create_order';
    case PaymentInitiated = 'payment_initiated';
    case PaymentFailed = 'payment_failed';
    case OrderPaid = 'order_paid';
    case OrderRefunded = 'order_refunded';
    case AddedToAccount = 'added_to_account';

    public function label(): string
    {
        return match($this) {
            self::CreateOrder => 'Order Created',
            self::PaymentInitiated => 'Payment Initiated',
            self::PaymentFailed => 'Payment Failed',
            self::OrderPaid => 'Order Paid',
            self::OrderRefunded => 'Order Refunded',
            self::AddedToAccount => 'Added To Account',
        };
    }

}
