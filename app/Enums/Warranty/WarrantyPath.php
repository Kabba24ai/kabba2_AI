<?php

namespace App\Enums\Warranty;

/**
 * Who owns the machine decides who pays, who is invoiced, and whether a
 * diagnostic fee applies.
 */
enum WarrantyPath: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::External => 'External',
        };
    }

    public function longLabel(): string
    {
        return match ($this) {
            self::Internal => 'Internal Equipment Warranty',
            self::External => 'External Customer Warranty',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Internal => 'bg-gray-100 text-gray-600 border border-gray-200',
            self::External => 'bg-teal-100 text-teal-700 border border-teal-200',
        };
    }
}
