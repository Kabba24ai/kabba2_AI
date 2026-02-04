<?php

namespace App\Enums\Orders;

enum OrderPaymentMethod : string
{
    case COD = 'COD';
    case Account = 'Account';
    case Card = 'Card';
    case Cash = 'Cash';      // Pay at Front Desk
    case Online = 'Online';  // Direct Bank Transfer
    case Cheque = 'Cheque';  // Paid by Cheque
    case Other = 'Other';    // Other Method

    public function label(): string
    {
        return match ($this) {
            self::COD => 'Cash on Delivery',
            self::Account => 'Account Payment',
            self::Card => 'Credit/Debit Card',
            self::Cash => 'Pay at Front Desk',
            self::Online => 'Direct Bank Transfer',
            self::Cheque => 'Check',
            self::Other => 'Other Payment Method',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
