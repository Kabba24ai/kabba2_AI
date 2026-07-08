<?php

namespace App\Enums\FieldService;

/**
 * The office's initial estimate of how the mission will likely resolve.
 * This is an expectation only — the real outcome is decided by the
 * technician's field assessment.
 */
enum FieldOperationalExpectation: string
{
    case Unknown                   = 'unknown';
    case FieldRepairExpected       = 'field_repair_expected';
    case RecoveryExpected          = 'recovery_expected';
    case DealerAssistanceExpected  = 'dealer_assistance_expected';
    case OperatorAssistanceExpected = 'operator_assistance_expected';
    case NoRepairExpected          = 'no_repair_expected';

    public function label(): string
    {
        return match ($this) {
            self::Unknown                    => 'Unknown',
            self::FieldRepairExpected        => 'Field Repair Expected',
            self::RecoveryExpected           => 'Recovery Expected',
            self::DealerAssistanceExpected   => 'Dealer Assistance Expected',
            self::OperatorAssistanceExpected => 'Operator Assistance Expected',
            self::NoRepairExpected           => 'No Repair Expected',
        };
    }
}
