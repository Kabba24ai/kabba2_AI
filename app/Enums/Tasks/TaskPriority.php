<?php

namespace App\Enums\Tasks;

enum TaskPriority: string
{
    case Low    = 'low';
    case Normal = 'normal';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Normal => 'Normal',
            self::High   => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low    => 'bg-gray-100 text-gray-600',
            self::Normal => 'bg-blue-100 text-blue-700',
            self::High   => 'bg-yellow-100 text-yellow-700',
            self::Urgent => 'bg-red-100 text-red-700',
        };
    }

    /** Solid hex for the card's left-edge priority stripe (Task Center board). */
    public function stripeColor(): string
    {
        return match ($this) {
            self::Low    => '#cbd5e1',
            self::Normal => '#94a3b8',
            self::High   => '#d97706',
            self::Urgent => '#dc2626',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Urgent => 1,
            self::High   => 2,
            self::Normal => 3,
            self::Low    => 4,
        };
    }
}
