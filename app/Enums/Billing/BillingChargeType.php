<?php

namespace App\Enums\Billing;

enum BillingChargeType: string
{
    case Fuel          = 'fuel';
    case Damage        = 'damage';
    case Extension     = 'extension';
    case ServiceTicket = 'service_ticket';
    case Cleaning      = 'cleaning';
    case Delivery      = 'delivery';
    case Misc          = 'misc';

    public function label(): string
    {
        return match($this) {
            self::Fuel          => 'Fuel Charge',
            self::Damage        => 'Damage Charge',
            self::Extension     => 'Order Enhancement',
            self::ServiceTicket => 'Service Ticket',
            self::Cleaning      => 'Cleaning Fee',
            self::Delivery      => 'Delivery Fee',
            self::Misc          => 'Miscellaneous',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Fuel          => 'bg-orange-100 text-orange-800',
            self::Damage        => 'bg-red-100 text-red-800',
            self::Extension     => 'bg-blue-100 text-blue-800',
            self::ServiceTicket => 'bg-purple-100 text-purple-800',
            self::Cleaning      => 'bg-teal-100 text-teal-800',
            self::Delivery      => 'bg-sky-100 text-sky-800',
            self::Misc          => 'bg-gray-100 text-gray-800',
        };
    }

    /** Returns true for charge types that may originate from the mobile app. */
    public function isMobileEligible(): bool
    {
        return in_array($this, [self::Fuel, self::Damage]);
    }

    /** Returns true for charge types that create a child Order row. */
    public function createsChildOrder(): bool
    {
        return $this === self::Extension;
    }
}
