<?php

namespace App\Enums\Equipments;

enum EquipmentCurrentStatus : string
{
    case Available = 'available';
    case Rented = 'rented';
    case Maintenance = 'maintenance';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match($this) {
            self::Available => 'Available',
            self::Rented => 'Rented',
            self::Maintenance => 'Maint. Hold',
            self::Damaged => 'Damaged',
        };
    }

}
