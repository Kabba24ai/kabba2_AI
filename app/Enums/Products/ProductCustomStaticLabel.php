<?php

namespace App\Enums\Products;

enum ProductCustomStaticLabel : string
{
    // IMPORTANT: Do not change the keys below if orders exist in the system.
    // Changing these keys without updating order data will cause issues.
    // If you must change them, update all relevant order data first.
    case rental_damage_waiver = 'Damage Waiver Protection';
    case rental_track_insurance = 'Thrown Track Coverage';

    public function label(): string
    {
        return match($this) {
            self::rental_damage_waiver => 'Damage Waiver Protection',
            self::rental_track_insurance => 'Thrown Track Coverage',
        };
    }

}
