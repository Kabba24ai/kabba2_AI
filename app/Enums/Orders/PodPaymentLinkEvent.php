<?php

namespace App\Enums\Orders;

enum PodPaymentLinkEvent: string
{
    case LinkCreated      = 'link_created';
    case LinkOpened       = 'link_opened';
    case Reminder1Sent    = 'reminder_1_sent';
    case Reminder2Sent    = 'reminder_2_sent';
    case Reminder3Sent    = 'reminder_3_sent';
    case Reminder4Sent    = 'reminder_4_sent';
    case PaymentCompleted = 'payment_completed';
    case OrderExpired     = 'order_expired';
    case OrderReactivated = 'order_reactivated';
    case ManualResend     = 'manual_resend';

    public function label(): string
    {
        return match($this) {
            self::LinkCreated      => 'Payment Link Created',
            self::LinkOpened       => 'Payment Link Opened',
            self::Reminder1Sent    => 'Reminder #1 Sent (1 hour after order)',
            self::Reminder2Sent    => 'Reminder #2 Sent (7 AM on rental start date)',
            self::Reminder3Sent    => 'Reminder #3 Sent (Last Chance — after start date)',
            self::Reminder4Sent    => 'Reminder #4 Sent (Closeout — 24h after R3)',
            self::PaymentCompleted => 'Payment Completed',
            self::OrderExpired     => 'Order Expired',
            self::OrderReactivated => 'Order Reactivated',
            self::ManualResend     => 'Payment Link Manually Resent (Admin)',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
