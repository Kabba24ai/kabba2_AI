<?php

namespace App\Enums\Customers;

enum Invoice: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Online = 'online';
    case Cheque = 'cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Pay at Front Desk',
            self::Card => 'CC on File',
            self::Online => 'Direct Bank Transfer',
            self::Cheque => 'Check Payment',
            self::Other => 'Other Payment Method',
        };
    }
}
