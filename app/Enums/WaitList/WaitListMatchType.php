<?php

namespace App\Enums\WaitList;

enum WaitListMatchType: string
{
    case ExactEquipment = 'exact_equipment';
    case Category       = 'category';

    public function label(): string
    {
        return match ($this) {
            self::ExactEquipment => 'Exact Equipment',
            self::Category       => 'Category',
        };
    }
}
