<?php

namespace App\Enums\AiRules;

/**
 * How far an AI rule has moved from policy to running automation. A rule can be
 * an approved, canonical definition long before any code automates it.
 */
enum AiRuleImplementationStatus: string
{
    case DefinedNotAutomated = 'defined_not_automated'; // approved policy, no automation yet
    case Active = 'active';                             // automation is live
    case Retired = 'retired';                           // superseded / withdrawn

    public function label(): string
    {
        return match ($this) {
            self::DefinedNotAutomated => 'Defined / Not Yet Automated',
            self::Active              => 'Active',
            self::Retired             => 'Retired',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::DefinedNotAutomated => 'bg-amber-100 text-amber-800',
            self::Active              => 'bg-green-100 text-green-800',
            self::Retired             => 'bg-gray-100 text-gray-800',
        };
    }
}
