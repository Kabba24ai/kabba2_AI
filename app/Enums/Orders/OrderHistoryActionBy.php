<?php

namespace App\Enums\Orders;

enum OrderHistoryActionBy : string
{
    case Customer = 'Customer';
    case User = 'User';
    case System = 'System';

    public function label(): string
    {
        return match($this) {
            self::Customer => 'Customer',
            self::User => 'User',
            self::System => 'System',
        };
    }
}
