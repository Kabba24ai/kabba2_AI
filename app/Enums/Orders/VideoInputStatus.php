<?php

namespace App\Enums\Orders;

enum VideoInputStatus: string
{
    case Pending   = 'Pending';
    case Completed = 'Completed';
    case Skipped   = 'Skipped';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Completed => 'Completed',
            self::Skipped   => 'Skipped',
        };
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
