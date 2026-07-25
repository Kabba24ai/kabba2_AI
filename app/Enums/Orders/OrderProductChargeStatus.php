<?php

namespace App\Enums\Orders;

enum OrderProductChargeStatus: string
{
    case Pending       = 'pending';
    case Completed     = 'completed';
    case Account       = 'account';
    case Resolved      = 'resolved';
    case Uncollectible = 'uncollectible';

    public function label(): string
    {
        return match($this) {
            self::Pending       => 'Pending',
            self::Completed     => 'Paid',
            self::Account       => 'On Account',
            self::Resolved      => 'Resolved',
            self::Uncollectible => 'Uncollectible',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending       => 'bg-amber-100 text-amber-800',
            self::Completed     => 'bg-green-100 text-green-800',
            self::Account       => 'bg-indigo-100 text-indigo-800',
            self::Resolved      => 'bg-blue-100 text-blue-800',
            self::Uncollectible => 'bg-gray-100 text-gray-500',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }
}
