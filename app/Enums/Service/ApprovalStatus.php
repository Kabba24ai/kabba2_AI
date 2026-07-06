<?php

namespace App\Enums\Service;

enum ApprovalStatus: string
{
    case NotRequired  = 'not_required';
    case Pending      = 'pending';
    case EstimateSent = 'estimate_sent';
    case Approved     = 'approved';
    case Declined     = 'declined';
    case Revoked      = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired  => 'Not Required',
            self::Pending      => 'Pending',
            self::EstimateSent => 'Estimate Sent',
            self::Approved     => 'Approved',
            self::Declined     => 'Declined',
            self::Revoked      => 'Revoked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotRequired  => 'bg-gray-100 text-gray-400',
            self::Pending      => 'bg-amber-100 text-amber-700',
            self::EstimateSent => 'bg-sky-100 text-sky-700',
            self::Approved     => 'bg-green-100 text-green-700',
            self::Declined     => 'bg-red-100 text-red-700',
            self::Revoked      => 'bg-red-100 text-red-600',
        };
    }

    /** Does this status satisfy the approval gate for repair authorization? */
    public function satisfied(): bool
    {
        return in_array($this, [self::NotRequired, self::Approved], true);
    }
}
