<?php

namespace App\Enums\Equipments;

enum EquipmentPowerSourceType: string
{
    case DIESEL = 'diesel';
    case GAS = 'gas';
    case BATTERIES = 'batteries';

    case ELECTRICS = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::DIESEL => 'Diesel',
            self::GAS => 'Gas',
            self::BATTERIES => 'Batteries',
            self::ELECTRICS => 'Electric',
        };
    }

}
