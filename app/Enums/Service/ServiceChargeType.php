<?php

namespace App\Enums\Service;

enum ServiceChargeType: string
{
    case Labor        = 'labor';
    case Parts        = 'parts';
    case Travel       = 'travel';
    case Mileage      = 'mileage';
    case Truck        = 'truck';
    case Trailer      = 'trailer';
    case Equipment    = 'equipment';
    case Fuel         = 'fuel';
    case ShopSupplies = 'shop_supplies';
    case Miscellaneous = 'miscellaneous';

    public function label(): string
    {
        return match ($this) {
            self::Labor        => 'Labor',
            self::Parts        => 'Parts',
            self::Travel       => 'Travel',
            self::Mileage      => 'Mileage',
            self::Truck        => 'Truck',
            self::Trailer      => 'Trailer',
            self::Equipment    => 'Equipment',
            self::Fuel         => 'Fuel',
            self::ShopSupplies => 'Shop Supplies',
            self::Miscellaneous => 'Miscellaneous',
        };
    }
}
