<?php

namespace App\Enums\Orders;

/**
 * Administrative disposition required before deleting a PAID extension
 * transaction that has no Kabba-recorded refund or void. The value and the
 * label are both written into the parent order's history so the audit trail
 * stays readable even if labels change later.
 */
enum ExtensionDeletionReason: string
{
    case RefundedThroughGateway   = 'refunded_through_gateway';
    case RefundedOutsideKabba     = 'refunded_outside_kabba';
    case DuplicateExtension       = 'duplicate_extension';
    case AdministrativeCorrection = 'administrative_correction';
    case Other                    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RefundedThroughGateway   => 'Refunded manually through Authorize.net',
            self::RefundedOutsideKabba     => 'Refunded outside Kabba',
            self::DuplicateExtension       => 'Duplicate extension',
            self::AdministrativeCorrection => 'Administrative correction',
            self::Other                    => 'Other',
        };
    }

    /** value => label pairs for select fields. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
