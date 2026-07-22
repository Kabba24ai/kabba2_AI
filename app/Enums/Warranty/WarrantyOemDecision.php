<?php

namespace App\Enums\Warranty;

/**
 * The manufacturer's decision on a submitted warranty claim (ST-4).
 * Approved = full coverage → straight to repair. Partial = OEM covers part,
 * customer decides on the balance. Denied = OEM covers nothing, customer is
 * offered a paid repair — both non-full outcomes route through the customer
 * decision.
 */
enum WarrantyOemDecision: string
{
    case Approved = 'approved';
    case Partial  = 'partial';
    case Denied   = 'denied';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Partial  => 'Partial Approval',
            self::Denied   => 'Denied',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'bg-green-100 text-green-700 border border-green-200',
            self::Partial  => 'bg-orange-100 text-orange-700 border border-orange-200',
            self::Denied   => 'bg-red-100 text-red-700 border border-red-200',
        };
    }
}
