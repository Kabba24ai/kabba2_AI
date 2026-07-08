<?php

namespace App\Enums\FieldService;

enum FieldRecoveryRisk: string
{
    case None     = 'none';
    case Possible = 'possible';
    case Likely   = 'likely';
    case Unknown  = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::None     => 'None',
            self::Possible => 'Possible',
            self::Likely   => 'Likely',
            self::Unknown  => 'Unknown',
        };
    }
}
