<?php

namespace App\Enums\WaitList;

/**
 * ExactEquipment is the canonical match: the returned unit is one of the
 * record's selected equipment IDs. Product marks the fallback for records
 * created during the short-lived product-based window; Category marks the
 * legacy category-wide fallback. Historical alerts keep their original
 * values.
 */
enum WaitListMatchType: string
{
    case ExactEquipment = 'exact_equipment';
    case Product        = 'product';
    case Category       = 'category';

    public function label(): string
    {
        return match ($this) {
            self::ExactEquipment => 'Selected Unit',
            self::Product        => 'Product (legacy)',
            self::Category       => 'Category (legacy)',
        };
    }
}
