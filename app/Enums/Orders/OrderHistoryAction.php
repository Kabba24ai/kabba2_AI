<?php

namespace App\Enums\Orders;

enum OrderHistoryAction : string
{
    case CreateOrder = 'create_order';
    case PaymentInitiated = 'payment_initiated';
    case PaymentFailed = 'payment_failed';
    case OrderPaid = 'order_paid';
    case OrderPartialRefund = 'order_partial_refunded';
    case OrderRefunded = 'order_refunded';
    case AddedToAccount = 'added_to_account';
    case TermsSigned = 'terms_signed';
    case LicenseUploaded = 'license_uploaded';
    case DeliveryMediaUploaded = 'delivery_media_uploaded';
    case ReturnMediaUploaded = 'return_media_uploaded';

    public function label(): string
    {
        return match($this) {
            self::CreateOrder => 'Order Created',
            self::PaymentInitiated => 'Payment Initiated',
            self::PaymentFailed => 'Payment Failed',
            self::OrderPaid => 'Order Paid',
            self::OrderPartialRefund => 'Order Partial Refund',
            self::OrderRefunded => 'Order Refunded',
            self::AddedToAccount => 'Added To Account',
            self::TermsSigned => 'Terms Signed',
            self::LicenseUploaded => 'License Uploaded',
            self::DeliveryMediaUploaded => 'Delivery Video Uploaded',
            self::ReturnMediaUploaded => 'Return Video Uploaded',
        };
    }

}
