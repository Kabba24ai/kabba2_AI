<?php

namespace App\Enums\WaitList;

enum WaitListRequestType: string
{
    case Category          = 'category';
    case SpecificEquipment = 'specific_equipment';

    public function label(): string
    {
        return match ($this) {
            self::Category          => 'Category Wait List',
            self::SpecificEquipment => 'Specific Equipment Wait List',
        };
    }
}
