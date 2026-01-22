<?php

namespace App\Enums\Equipments;

enum EquipmentPowerSourceType: string
{
    case DIESEL = 'diesel';
    case GAS = 'gas';
    case BATTERIES = 'batteries';

    case ELECTRICS = 'electric';

}
