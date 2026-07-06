<?php

namespace App\Enums\WaitList;

enum WaitListStorePreference: string
{
    case AnyStore                 = 'any_store';
    case SpecificStore            = 'specific_store';
    case PreferredTransferAllowed = 'preferred_store_transfer_allowed';

    public function label(): string
    {
        return match ($this) {
            self::AnyStore                 => 'Any Store',
            self::SpecificStore            => 'Specific Store',
            self::PreferredTransferAllowed => 'Preferred Store, Transfer Allowed',
        };
    }
}
