<?php

namespace App\Enums\Discounts;

/**
 * How a discount amount is derived. Expected pairings: store_credit + goodwill
 * → fixed_amount; general → percentage.
 */
enum DiscountCalculationType: string
{
    case FixedAmount = 'fixed_amount';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::FixedAmount => 'Fixed Amount',
            self::Percentage  => 'Percentage',
        };
    }
}
