<?php

namespace App\Enums\Billing;

enum BillingChargeStatus: string
{
    case Pending       = 'pending';
    case Paid          = 'paid';
    case Resolved      = 'resolved';
    case Uncollectible = 'uncollectible';
    case Voided        = 'voided';

    public function label(): string
    {
        return match($this) {
            self::Pending       => 'Pending',
            self::Paid          => 'Paid',
            self::Resolved      => 'Resolved',
            self::Uncollectible => 'Uncollectible',
            self::Voided        => 'Voided',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending       => 'bg-amber-100 text-amber-800',
            self::Paid          => 'bg-green-100 text-green-800',
            self::Resolved      => 'bg-blue-100 text-blue-800',
            self::Uncollectible => 'bg-gray-100 text-gray-500',
            self::Voided        => 'bg-red-100 text-red-400',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }
}
