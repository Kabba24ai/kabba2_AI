<?php

namespace App\Enums\Orders;

enum EquipmentDriverStatus: string
{
    case READY_TO_GO = 'Ready to Go';
    case ON_MY_WAY   = 'On My Way';
    case ARRIVED     = 'Arrived';

    public function label(): string
    {
        return match($this) {
            self::READY_TO_GO => 'Ready to Go',
            self::ON_MY_WAY   => 'On My Way',
            self::ARRIVED     => 'Arrived',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
