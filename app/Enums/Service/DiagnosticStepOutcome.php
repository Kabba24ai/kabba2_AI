<?php

namespace App\Enums\Service;

enum DiagnosticStepOutcome: string
{
    case Resolved            = 'resolved';
    case Failed              = 'failed';
    case NotApplicable       = 'not_applicable';
    case NeedsFurtherTesting = 'needs_further_testing';

    public function label(): string
    {
        return match ($this) {
            self::Resolved            => 'Resolved',
            self::Failed              => 'Failed',
            self::NotApplicable       => 'Not Applicable',
            self::NeedsFurtherTesting => 'Needs Further Testing',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Resolved            => 'bg-green-100 text-green-700',
            self::Failed              => 'bg-red-100 text-red-700',
            self::NotApplicable       => 'bg-gray-100 text-gray-500',
            self::NeedsFurtherTesting => 'bg-amber-100 text-amber-700',
        };
    }
}
