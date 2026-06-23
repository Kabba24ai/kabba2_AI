<?php

namespace App\Enums\Orders;

enum PodPaymentLinkEvent: string
{
    case LinkCreated      = 'link_created';
    case PaymentLinkSent  = 'payment_link_sent';
    case LinkOpened       = 'link_opened';
    case Reminder1Sent    = 'reminder_1_sent';
    case Reminder2Sent    = 'reminder_2_sent';
    case Reminder3Sent    = 'reminder_3_sent';
    case Reminder4Sent    = 'reminder_4_sent';
    case PaymentCompleted = 'payment_completed';
    case OrderExpired     = 'order_expired';
    case OrderReactivated = 'order_reactivated';
    case ManualResend      = 'manual_resend';
    case FinalReminderSent = 'final_reminder_sent';
    case LastDitchSent     = 'last_ditch_sent';

    public function label(): string
    {
        return match($this) {
            self::LinkCreated      => 'Payment Link Created',
            self::PaymentLinkSent  => 'Payment Link SMS Sent (1 min after order confirmation)',
            self::LinkOpened       => 'Payment Link Opened',
            self::Reminder1Sent    => 'Reminder #1 Sent (1 min. after order confirmation)',
            self::Reminder2Sent    => 'Reminder #2 Sent (After Next day scheduled rental occuring SMS)',
            self::Reminder3Sent    => 'Reminder #3 Sent (Last Chance — after start date)',
            self::Reminder4Sent    => 'Reminder #4 Sent (Closeout — 24h after R3)',
            self::PaymentCompleted => 'Payment Completed',
            self::OrderExpired     => 'Order Expired',
            self::OrderReactivated => 'Order Reactivated',
            self::ManualResend      => 'Payment Link Manually Resent (Admin)',
            self::FinalReminderSent => 'Final Rental Reminder Sent (9:00 AM on delivery day)',
            self::LastDitchSent     => 'Last Ditch Recovery Message Sent (4:00 PM on delivery day)',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
