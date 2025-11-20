<?php

namespace App\Enums\Orders;

enum OrderMediaType: string
{
    case LICENSE = 'license';
    case DELIVERY = 'delivery';
    case PICKUP = 'pickup';

    public function label(): string
    {
        return match($this) {
            self::LICENSE => 'License',
            self::DELIVERY => 'Delivery',
            self::PICKUP => 'Pickup',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
