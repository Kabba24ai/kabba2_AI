<?php

namespace App\Enums\Dispatch;

enum DispatchIntelligenceRuleSource: string
{
    case Admin           = 'admin';
    case AiGenerated     = 'ai_generated';
    case Experience      = 'experience';
    case CustomerSpecific = 'customer_specific';
    case DriverFeedback  = 'driver_feedback';

    public function label(): string
    {
        return match($this) {
            self::Admin            => 'Admin',
            self::AiGenerated      => 'AI Generated',
            self::Experience       => 'Field Experience',
            self::CustomerSpecific => 'Customer Specific',
            self::DriverFeedback   => 'Driver Feedback',
        };
    }

    public function badgeColor(): string
    {
        return match($this) {
            self::Admin            => 'bg-gray-100 text-gray-700',
            self::AiGenerated      => 'bg-purple-100 text-purple-700',
            self::Experience       => 'bg-green-100 text-green-700',
            self::CustomerSpecific => 'bg-blue-100 text-blue-700',
            self::DriverFeedback   => 'bg-amber-100 text-amber-700',
        };
    }
}
