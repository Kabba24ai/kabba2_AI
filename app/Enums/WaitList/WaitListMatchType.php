<?php

namespace App\Enums\WaitList;

/**
 * Product is the unified-workflow match (returned unit's product is one of
 * the record's selected acceptable products). ExactEquipment and Category
 * are LEGACY values kept so historical alerts display truthfully; they are
 * still produced only for un-migrated legacy records (see WaitListMatcher).
 */
enum WaitListMatchType: string
{
    case Product        = 'product';
    case ExactEquipment = 'exact_equipment';
    case Category       = 'category';

    public function label(): string
    {
        return match ($this) {
            self::Product        => 'Acceptable Product',
            self::ExactEquipment => 'Exact Equipment',
            self::Category       => 'Category',
        };
    }
}
