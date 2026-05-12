<?php

namespace App\Enums\Equipments;

enum EquipmentKeyStartingMechanism: string
{
    case NONE = 'none';
    case ONE_KEY = '1_key';
    case TWO_KEYS = '2_keys';
    case KEY_PAD = 'key_pad';
    case PULL_CORD = 'pull_cord';

    public function label(): string
    {
        return match($this) {
            self::NONE => 'None',
            self::ONE_KEY => '1 Key',
            self::TWO_KEYS => '2 Keys',
            self::KEY_PAD => 'Key Pad',
            self::PULL_CORD => 'Pull Cord',
        };
    }
}
