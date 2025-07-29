<?php

namespace App\Enums\Orders;

enum OrderPaymentMethod : string
{
    case COD = 'COD';
    case Account = 'Account';
    case Card = 'Card';

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Cash on Delivery',
            self::Account => 'Account Payment',
            self::Card => 'Credit/Debit Card',
        };
    }
}
