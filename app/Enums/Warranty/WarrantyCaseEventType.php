<?php

namespace App\Enums\Warranty;

enum WarrantyCaseEventType: string
{
    case Created      = 'created';
    case QueueChanged = 'queue_changed';
    case FeeUpdated   = 'fee_updated';

    public function label(): string
    {
        return match ($this) {
            self::Created      => 'Case Created',
            self::QueueChanged => 'Queue Changed',
            self::FeeUpdated   => 'Diagnostic Fee Updated',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created      => 'bg-green-400',
            self::QueueChanged => 'bg-purple-500',
            self::FeeUpdated   => 'bg-teal-400',
        };
    }
}
