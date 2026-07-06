<?php

namespace App\Enums\WaitList;

enum WaitListAlertStatus: string
{
    case Unacknowledged = 'unacknowledged';
    case Acknowledged   = 'acknowledged';
    case Dismissed      = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Unacknowledged => 'Needs Attention',
            self::Acknowledged   => 'Acknowledged',
            self::Dismissed      => 'Dismissed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unacknowledged => 'bg-red-100 text-red-700',
            self::Acknowledged   => 'bg-green-100 text-green-700',
            self::Dismissed      => 'bg-gray-100 text-gray-500',
        };
    }
}
