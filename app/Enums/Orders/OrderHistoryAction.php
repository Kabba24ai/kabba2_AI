<?php

namespace App\Enums\Orders;

enum OrderHistoryAction: string
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
    case CodSmsNotification = 'cod_sms_notification';
    case BillingAddressUpdated = 'billing_address_updated';
    case DeliveryAddressUpdated = 'delivery_address_updated';
    case NoteCreated = 'note_created';
    case NoteUpdated = 'note_updated';
    case NoteDeleted = 'note_deleted';
    case ProductScheduleUpdated = 'product_schedule_updated';
    case ChecklistDelivered = 'checklist_delivered';
    case ChecklistReturned = 'checklist_returned';
    case ChecklistRemoved = 'checklist_removed';
    case TermsFirstRequest = 'terms_first_request';
    case TermsSecondRequest = 'terms_second_request';
    case TermsThirdRequest = 'terms_third_request';

    case PaymentCollected = 'payment_collected';
    case PaymentUncollectable = 'payment_uncollectable';
    case PartialPaymentReceived = 'partial_payment_received';

    public function label(): string
    {
        return match ($this) {
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
            self::CodSmsNotification => 'POD SMS Notification Sent',
            self::BillingAddressUpdated => 'Billing Address Updated',
            self::DeliveryAddressUpdated => 'Delivery Address Updated',
            self::NoteCreated => 'Note Created',
            self::NoteUpdated => 'Note Updated',
            self::NoteDeleted => 'Note Deleted',
            self::ProductScheduleUpdated => 'Product Schedule Updated',
            self::ChecklistDelivered => 'Customer Checklist Delivered',
            self::ChecklistReturned => 'Customer Checklist Returned',
            self::ChecklistRemoved => 'Customer Checklist Removed',
            self::TermsFirstRequest => 'Terms First Request Sent',
            self::TermsSecondRequest => 'Terms Second Request Sent',
            self::TermsThirdRequest => 'Terms Third Request Sent',
            self::PaymentCollected => 'Payment Collected',
            self::PaymentUncollectable => 'Payment Marked Uncollectable',
            self::PartialPaymentReceived => 'Partial Payment Received',
        };
    }
}
