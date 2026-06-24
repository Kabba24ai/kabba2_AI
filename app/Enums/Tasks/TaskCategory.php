<?php

namespace App\Enums\Tasks;

enum TaskCategory: string
{
    case Sales = 'sales';
    case Yard  = 'yard';
    case Shop  = 'shop';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales',
            self::Yard  => 'Yard',
            self::Shop  => 'Shop',
            self::Admin => 'Admin',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sales => 'bg-blue-100 text-blue-700',
            self::Yard  => 'bg-green-100 text-green-700',
            self::Shop  => 'bg-orange-100 text-orange-700',
            self::Admin => 'bg-purple-100 text-purple-700',
        };
    }
}
