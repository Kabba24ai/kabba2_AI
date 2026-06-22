<?php

namespace App\Enums\Orders;

enum PodPaymentLinkStatus: string
{
    case Pending      = 'pending';       // Link created, never opened
    case Opened       = 'opened';        // Link opened at least once
    case Expired      = 'expired';       // Order expired without payment
    case Reactivated  = 'reactivated';   // Order reactivated after expiry
    case Completed    = 'completed';     // Payment received

    public function label(): string
    {
        return match($this) {
            self::Pending     => 'Pending',
            self::Opened      => 'Opened',
            self::Expired     => 'Expired',
            self::Reactivated => 'Reactivated',
            self::Completed   => 'Completed',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isExpired(): bool
    {
        return $this === self::Expired;
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
