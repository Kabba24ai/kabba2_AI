<?php

namespace App\Enums\Dispatch;

enum DispatchIntelligenceRuleType: string
{
    case DriverAssignment   = 'driver_assignment';
    case Routing            = 'routing';
    case Scheduling         = 'scheduling';
    case Loading            = 'loading';
    case CustomerPreference = 'customer_preference';
    case PriorityOverride   = 'priority_override';
    case Seasonal           = 'seasonal';
    case Geographic         = 'geographic';
    case Safety             = 'safety';
    case Operational        = 'operational';

    public function label(): string
    {
        return match($this) {
            self::DriverAssignment   => 'Driver Assignment',
            self::Routing            => 'Routing',
            self::Scheduling         => 'Scheduling',
            self::Loading            => 'Loading',
            self::CustomerPreference => 'Customer Preference',
            self::PriorityOverride   => 'Priority Override',
            self::Seasonal           => 'Seasonal',
            self::Geographic         => 'Geographic',
            self::Safety             => 'Safety',
            self::Operational        => 'Operational',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::DriverAssignment   => 'bg-blue-100 text-blue-800',
            self::Routing            => 'bg-purple-100 text-purple-800',
            self::Scheduling         => 'bg-indigo-100 text-indigo-800',
            self::Loading            => 'bg-orange-100 text-orange-800',
            self::CustomerPreference => 'bg-pink-100 text-pink-800',
            self::PriorityOverride   => 'bg-red-100 text-red-800',
            self::Seasonal           => 'bg-yellow-100 text-yellow-800',
            self::Geographic         => 'bg-teal-100 text-teal-800',
            self::Safety             => 'bg-rose-100 text-rose-800',
            self::Operational        => 'bg-gray-100 text-gray-800',
        };
    }
}
