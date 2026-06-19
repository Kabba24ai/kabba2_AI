<?php

namespace App\Enums\EquipmentAi;

enum IntelligenceRuleSource: string
{
    case Admin              = 'admin';
    case AiGenerated        = 'ai_generated';
    case Manufacturer       = 'manufacturer';
    case Dealer             = 'dealer';
    case InternalExperience = 'internal_experience';

    public function label(): string
    {
        return match($this) {
            self::Admin              => 'Admin',
            self::AiGenerated        => 'AI Generated',
            self::Manufacturer       => 'Manufacturer',
            self::Dealer             => 'Dealer',
            self::InternalExperience => 'Internal Experience',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::Admin              => 'gray',
            self::AiGenerated        => 'purple',
            self::Manufacturer       => 'blue',
            self::Dealer             => 'teal',
            self::InternalExperience => 'green',
        };
    }
}
