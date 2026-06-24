<?php

namespace App\Enums\Tasks;

enum TaskStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Waiting    = 'waiting';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Open',
            self::InProgress => 'In Progress',
            self::Waiting    => 'Waiting',
            self::Completed  => 'Completed',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open       => 'bg-blue-100 text-blue-700',
            self::InProgress => 'bg-yellow-100 text-yellow-700',
            self::Waiting    => 'bg-orange-100 text-orange-700',
            self::Completed  => 'bg-green-100 text-green-700',
            self::Cancelled  => 'bg-gray-100 text-gray-500',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled]);
    }
}
