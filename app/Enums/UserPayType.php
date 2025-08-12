<?php

namespace App\Enums;

enum UserPayType: string
{
    case Hourly = 'Hourly';
    case Salary = 'Salary';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => 'Hourly Pay',
            self::Salary => 'Salary Pay',
        };
    }

    // Helper: Get array for dropdown
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();
    }
}
