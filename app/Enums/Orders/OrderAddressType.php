<?php

namespace App\Enums\Orders;

enum OrderAddressType : string
{
    case Billing = 'Billing';
    case Delivery = 'Delivery';

    public function label(): string
    {
        return match($this) {
            self::Billing => 'Billing',
            self::Delivery => 'Delivery',
        };
    }

    public function isBilling(): bool
    {
        return $this === self::Billing;
    }

    public function isDelivery(): bool
    {
        return $this === self::Delivery;
    }
}
