<?php

namespace App\Enums\FieldService;

enum FieldSiteAccess: string
{
    case Normal    = 'normal';
    case Limited   = 'limited';
    case Difficult = 'difficult';
    case Unknown   = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Normal    => 'Normal',
            self::Limited   => 'Limited',
            self::Difficult => 'Difficult',
            self::Unknown   => 'Unknown',
        };
    }
}
