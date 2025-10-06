<?php

namespace App\Enums\Orders;

enum OrderPaymentMethod : string
{
    case COD = 'COD';
    case Account = 'Account';
    case Card = 'Card';
    case Cash = 'Cash';      // Pay at Front Desk
    case Online = 'Online';  // Direct Bank Transfer

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Cash on Delivery',
            self::Account => 'Account Payment',
            self::Card => 'Credit/Debit Card',
            self::Cash => 'Pay at Front Desk',
            self::Online => 'Direct Bank Transfer',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
