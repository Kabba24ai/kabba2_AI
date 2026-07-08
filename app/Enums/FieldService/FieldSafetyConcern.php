<?php

namespace App\Enums\FieldService;

enum FieldSafetyConcern: string
{
    case None        = 'none';
    case Minor       = 'minor';
    case Significant = 'significant';
    case Unknown     = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::None        => 'None',
            self::Minor       => 'Minor',
            self::Significant => 'Significant',
            self::Unknown     => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None        => 'bg-gray-100 text-gray-600 border border-gray-200',
            self::Minor       => 'bg-amber-100 text-amber-700 border border-amber-200',
            self::Significant => 'bg-red-100 text-red-700 border border-red-200',
            self::Unknown     => 'bg-slate-100 text-slate-600 border border-slate-200',
        };
    }
}
