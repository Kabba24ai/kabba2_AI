<?php

namespace App\Enums\WaitList;

/**
 * Category and SpecificEquipment are LEGACY values kept so historical
 * records display truthfully. Every record created since the unified
 * workflow (customer + one category + one or more acceptable products)
 * uses Unified; matching no longer branches on this value.
 */
enum WaitListRequestType: string
{
    case Unified           = 'unified';
    case Category          = 'category';
    case SpecificEquipment = 'specific_equipment';

    public function label(): string
    {
        return match ($this) {
            self::Unified           => 'Equipment Wait List',
            self::Category          => 'Category Wait List (legacy)',
            self::SpecificEquipment => 'Specific Equipment Wait List (legacy)',
        };
    }
}
