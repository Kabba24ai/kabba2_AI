<?php

namespace App\Enums\WaitList;

enum WaitListCommunicationType: string
{
    case Called            = 'called';
    case LeftVoicemail     = 'left_voicemail';
    case SpokeWithCustomer = 'spoke_with_customer';
    case TextedManually    = 'texted_manually';
    case CustomerAccepted  = 'customer_accepted';
    case CustomerDeclined  = 'customer_declined';
    case NoAnswer          = 'no_answer';
    case WillCallBack      = 'will_call_back';
    case InternalNote      = 'internal_note';

    public function label(): string
    {
        return match ($this) {
            self::Called            => 'Called',
            self::LeftVoicemail     => 'Left Voicemail',
            self::SpokeWithCustomer => 'Spoke with Customer',
            self::TextedManually    => 'Texted Manually',
            self::CustomerAccepted  => 'Customer Accepted',
            self::CustomerDeclined  => 'Customer Declined',
            self::NoAnswer          => 'No Answer',
            self::WillCallBack      => 'Will Call Back',
            self::InternalNote      => 'Internal Note',
        };
    }
}
