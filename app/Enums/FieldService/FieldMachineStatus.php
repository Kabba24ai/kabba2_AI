<?php

namespace App\Enums\FieldService;

enum FieldMachineStatus: string
{
    case Operable         = 'operable';
    case LimitedOperation = 'limited_operation';
    case Inoperable       = 'inoperable';
    case Unknown          = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Operable         => 'Operable',
            self::LimitedOperation => 'Limited Operation',
            self::Inoperable       => 'Inoperable',
            self::Unknown          => 'Unknown',
        };
    }
}
