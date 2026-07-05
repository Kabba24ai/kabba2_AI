<?php

namespace App\Enums\Service;

enum ServiceLocation: string
{
    case InShop       = 'in_shop';
    case CustomerSite = 'customer_site';

    public function label(): string
    {
        return match ($this) {
            self::InShop       => 'In Shop',
            self::CustomerSite => 'Customer Site',
        };
    }
}
