<?php

namespace App\Enums\Orders;

enum DriversLicenseStatus: string
{
    case Pending  = 'Pending';
    case Verified = 'Verified';
    case Rejected = 'Rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Pending',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
