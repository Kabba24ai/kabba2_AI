<?php

namespace App\Enums\FieldService;

/** Three-state answer used for dispatch-assessment questions (e.g. machine stuck). */
enum FieldYesNoUnknown: string
{
    case Yes     = 'yes';
    case No      = 'no';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Yes     => 'Yes',
            self::No      => 'No',
            self::Unknown => 'Unknown',
        };
    }
}
