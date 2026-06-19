<?php

namespace App\Enums\EquipmentAi;

enum IntelligenceRuleType: string
{
    case Suitability        = 'suitability';
    case Limitation         = 'limitation';
    case Substitution       = 'substitution';
    case Scheduling         = 'scheduling';
    case Safety             = 'safety';
    case Delivery           = 'delivery';
    case Productivity       = 'productivity';
    case Terrain            = 'terrain';
    case CustomerPreference = 'customer_preference';
    case ApplicationUseCase = 'application_use_case';

    public function label(): string
    {
        return match($this) {
            self::Suitability        => 'Suitability',
            self::Limitation         => 'Limitation',
            self::Substitution       => 'Substitution',
            self::Scheduling         => 'Scheduling',
            self::Safety             => 'Safety',
            self::Delivery           => 'Delivery',
            self::Productivity       => 'Productivity',
            self::Terrain            => 'Terrain',
            self::CustomerPreference => 'Customer Preference',
            self::ApplicationUseCase => 'Application / Use Case',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::Suitability        => 'blue',
            self::Limitation         => 'red',
            self::Substitution       => 'purple',
            self::Scheduling         => 'orange',
            self::Safety             => 'rose',
            self::Delivery           => 'amber',
            self::Productivity       => 'green',
            self::Terrain            => 'teal',
            self::CustomerPreference => 'indigo',
            self::ApplicationUseCase => 'cyan',
        };
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
