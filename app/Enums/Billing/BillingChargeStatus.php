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

    /**
     * Canonical status palette (Billing Charge Operations Commonization —
     * approved): Pending=orange, Paid=green, Resolved=blue,
     * Uncollectible=gray, Voided=gray struck through. Every surface renders
     * this vocabulary via x-admin.billing.charge-status-badge /
     * BillingChargePresenter — do not re-declare status colors in views.
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::Pending       => 'bg-orange-50 text-orange-700 border-orange-200',
            self::Paid          => 'bg-green-50 text-green-700 border-green-200',
            self::Resolved      => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Uncollectible => 'bg-gray-100 text-gray-500 border-gray-200',
            self::Voided        => 'bg-gray-100 text-gray-400 border-gray-200 line-through',
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
