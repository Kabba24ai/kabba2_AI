<?php

namespace App\Enums\Warranty;

/**
 * The customer's decision after a partial/denied OEM outcome (ST-4):
 * proceed with the (partly or fully) customer-paid repair, or decline and
 * close the case.
 */
enum WarrantyCustomerDecision: string
{
    case Proceed = 'proceed';
    case Decline = 'decline';

    public function label(): string
    {
        return match ($this) {
            self::Proceed => 'Proceed with Repair',
            self::Decline => 'Decline — Close Case',
        };
    }
}
