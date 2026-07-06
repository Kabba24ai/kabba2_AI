<?php

namespace App\Enums\WaitList;

enum WaitListStatus: string
{
    case Active       = 'active';
    case Acknowledged = 'acknowledged';
    case Converted    = 'converted';
    case Cancelled    = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active       => 'Active',
            self::Acknowledged => 'Acknowledged',
            self::Converted    => 'Converted',
            self::Cancelled    => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active       => 'bg-green-100 text-green-700',
            self::Acknowledged => 'bg-sky-100 text-sky-700',
            self::Converted    => 'bg-indigo-100 text-indigo-700',
            self::Cancelled    => 'bg-gray-100 text-gray-500',
        };
    }

    /** Statuses that still represent live customer demand for matching. */
    public static function waiting(): array
    {
        return [self::Active->value, self::Acknowledged->value];
    }
}
