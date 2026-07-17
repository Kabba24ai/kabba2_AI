<?php

namespace App\Enums\Orders;

enum OrderTermsStatus : string
{
    case Accepted = 'Accepted';
    case Declined = 'Declined';
    case Pending = 'Pending';
    case Exempt = 'Exempt';

    public function label(): string
    {
        return match($this) {
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Pending => 'Pending',
            self::Exempt => 'Exempt',
        };
    }

    public function isAccepted(): bool
    {
        return $this === self::Accepted;
    }

    public function isDeclined(): bool
    {
        return $this === self::Declined;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isExempt(): bool
    {
        return $this === self::Exempt;
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

}
