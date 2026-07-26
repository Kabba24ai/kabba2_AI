<?php

namespace App\Enums\Credit;

/**
 * Coarse origin of the posting that pushed a customer over their approved
 * credit threshold. The precise human detail (e.g. "Fuel Charge") is kept in
 * the event's `source_detail` snapshot; this enum is only the broad bucket.
 */
enum CreditThresholdSourceType: string
{
    case AccountCharge = 'account_charge';   // fuel / damage / manual CRM charge / invoice line
    case OrderOnAccount = 'order_on_account'; // checkout or convert-to-account order exposure
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AccountCharge  => 'Account Charge',
            self::OrderOnAccount => 'On-Account Order',
            self::Other          => 'Other',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::AccountCharge  => 'bg-orange-100 text-orange-800',
            self::OrderOnAccount => 'bg-blue-100 text-blue-800',
            self::Other          => 'bg-gray-100 text-gray-800',
        };
    }
}
