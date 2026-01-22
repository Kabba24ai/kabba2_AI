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

    public static function getValues(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }

    public function isRented(): bool
    {
        return $this === self::Rented;
    }

    public function isAvailable(): bool
    {
        return $this === self::Available;
    }

    public function isMaintenance(): bool
    {
        return $this === self::Maintenance;
    }

    public function isDamaged(): bool
    {
        return $this === self::Damaged;
    }
}
