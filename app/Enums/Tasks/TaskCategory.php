<?php

namespace App\Enums\Tasks;

enum TaskCategory: string
{
    // Case order is display order — dropdowns and filter badges follow it.
    case Sales   = 'sales';
    case Admin   = 'admin';
    case Billing = 'billing';
    case Yard    = 'yard';
    case Shop    = 'shop';

    public function label(): string
    {
        return match ($this) {
            self::Sales   => 'Sales',
            self::Admin   => 'Admin',
            self::Billing => 'Billing',
            self::Yard    => 'Yard',
            self::Shop    => 'Shop',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sales   => 'bg-blue-100 text-blue-700',
            self::Admin   => 'bg-purple-100 text-purple-700',
            self::Billing => 'bg-teal-100 text-teal-700',
            self::Yard    => 'bg-green-100 text-green-700',
            self::Shop    => 'bg-orange-100 text-orange-700',
        };
    }
}
