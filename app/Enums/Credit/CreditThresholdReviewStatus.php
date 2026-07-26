<?php

namespace App\Enums\Credit;

/**
 * Review lifecycle of a credit-threshold event, mirrored from the linked
 * management-review task's terminal state so history can be queried without
 * joining Task Manager.
 */
enum CreditThresholdReviewStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open     => 'Open',
            self::Resolved => 'Resolved',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Open     => 'bg-amber-100 text-amber-800',
            self::Resolved => 'bg-green-100 text-green-800',
        };
    }
}
